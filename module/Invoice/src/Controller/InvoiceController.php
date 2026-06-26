<?php

declare(strict_types=1);

namespace Invoice\Controller;

use Invoice\Service\ApiClient;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use Laminas\Session\Container as SessionContainer;

/**
 * InvoiceController
 * ------------------
 * Gère tout le flux ERP : Dashboard, Upload, Validation, Historique,
 * Détail d'une facture, et Recherche intelligente.
 *
 * Authentification LOCALE (pas de Django) : vérifiée via onDispatch()
 * ci-dessous, qui bloque l'accès si aucune session 'erp_user' active.
 *
 * Toute la logique métier (extraction IA, sauvegarde, recherche) est
 * déléguée à l'API Python via ApiClient — ce contrôleur ne fait
 * qu'orchestrer l'affichage et les formulaires.
 *
 * VISIBILITÉ DES FACTURES :
 * - role === 'admin'    → voit et consulte TOUTES les factures
 * - role === 'employee' → ne voit et ne peut consulter QUE ses propres
 *                          factures (celles créées avec son user_id)
 * Le filtrage réel se fait côté API Python / base de données ; ce
 * contrôleur se contente de transmettre user_id + role à chaque appel.
 */
class InvoiceController extends AbstractActionController
{
    private ?array $currentUser = null;

    public function __construct(private ApiClient $apiClient)
    {
    }

    /**
     * Vérifie l'authentification AVANT chaque action de ce contrôleur.
     * Si non connecté → redirige vers /login.
     */
    public function onDispatch(MvcEvent $event)
    {
        $session = new SessionContainer('erp_user');

        if (empty($session->user_id)) {
            return $this->redirect()->toRoute('login');
        }

        $this->currentUser = [
            'user_id'     => $session->user_id,
            'username'    => $session->username,
            'fullname'    => $session->fullname,
            'licence_key' => $session->licence_key,
            'role'        => $session->role ?? 'employee',
        ];

        // Injecte automatiquement currentUser dans toutes les vues de ce
        // contrôleur, sans avoir à le répéter dans chaque ViewModel.
        $event->getViewModel()->setVariable('currentUser', $this->currentUser);

        return parent::onDispatch($event);
    }

    // -----------------------------------------------------------------
    // Dashboard : statistiques + dernières factures
    // -----------------------------------------------------------------
    public function indexAction(): ViewModel
    {
        $apiOnline = $this->apiClient->healthCheck();

        $stats = ['count' => 0, 'results' => []];
        if ($apiOnline) {
            try {
                $stats = $this->apiClient->listInvoices(
                    limit: 5,
                    offset: 0,
                    userId: (int) $this->currentUser['user_id'],
                    role: $this->currentUser['role'],
                );
            } catch (\RuntimeException $e) {
                // L'API répond au health check mais une erreur survient sur la lecture
                $apiOnline = false;
            }
        }

        return new ViewModel([
            'apiOnline'      => $apiOnline,
            'totalFactures'  => $stats['count'],
            'dernieresFactures' => $stats['results'],
            'currentUser'    => $this->currentUser,
        ]);
    }

    // -----------------------------------------------------------------
    // Upload : affiche le formulaire d'upload (GET) et lance
    // l'extraction IA quand un fichier est soumis (POST)
    // -----------------------------------------------------------------
    public function uploadAction(): ViewModel
    {
        $request = $this->getRequest();

        if (!$request->isPost()) {
            return new ViewModel([
                'currentUser' => $this->currentUser,
            ]);
        }

        $files = $this->getRequest()->getFiles()->toArray();

        if (empty($files['facture']) || $files['facture']['error'] !== UPLOAD_ERR_OK) {
            return new ViewModel([
                'error'       => 'Aucun fichier valide reçu. Vérifie le format (PDF, JPG, PNG).',
                'currentUser' => $this->currentUser,
            ]);
        }

        $uploadedFile = $files['facture'];

        try {
            $extracted = $this->apiClient->extractInvoice(
                $uploadedFile['tmp_name'],
                $uploadedFile['name'],
                $this->currentUser['licence_key']
            );
        } catch (\RuntimeException $e) {
            return new ViewModel([
                'error'       => $e->getMessage(),
                'currentUser' => $this->currentUser,
            ]);
        }

        if (empty($extracted['success'])) {
            return new ViewModel([
                'error'       => 'Extraction échouée : ' . ($extracted['detail'] ?? 'Erreur inconnue'),
                'currentUser' => $this->currentUser,
            ]);
        }

        // On stocke les données extraites en session pour les retrouver
        // sur la page de validation (l'utilisateur va les corriger).
        $container = new \Laminas\Session\Container('invoice_draft');
        $container->data        = $extracted['data'];
        $container->rawText     = $extracted['raw_text'] ?? '';
        $container->sourceFile  = $extracted['source_file'] ?? $uploadedFile['name'];

        return $this->redirect()->toRoute('invoice-validate');
    }

    // -----------------------------------------------------------------
    // Validate : affiche le formulaire pré-rempli (GET) avec les
    // données extraites, et sauvegarde en base après correction (POST)
    // -----------------------------------------------------------------
    public function validateAction(): ViewModel
    {
        $container = new \Laminas\Session\Container('invoice_draft');
        $request = $this->getRequest();

        if ($request->isPost()) {
            $post = $request->getPost()->toArray();

            $items = [];
            $descriptions = $post['item_description'] ?? [];
            foreach ($descriptions as $i => $desc) {
                if (trim($desc) === '') {
                    continue;
                }
                $items[] = [
                    'description' => $desc,
                    'quantity'    => isset($post['item_quantity'][$i]) ? (float) $post['item_quantity'][$i] : null,
                    'unit_price'  => isset($post['item_unit_price'][$i]) ? (float) $post['item_unit_price'][$i] : null,
                    'total_price' => isset($post['item_total_price'][$i]) ? (float) $post['item_total_price'][$i] : null,
                ];
            }

            $invoiceData = [
                'invoice_number' => $post['invoice_number'] ?? null,
                'invoice_date'   => $post['invoice_date'] ?: null,
                'due_date'       => $post['due_date'] ?: null,
                'vendor_name'    => $post['vendor_name'] ?? null,
                'vendor_address' => $post['vendor_address'] ?? null,
                'vendor_email'   => $post['vendor_email'] ?? null,
                'vendor_phone'   => $post['vendor_phone'] ?? null,
                'client_name'    => $post['client_name'] ?? null,
                'client_address' => $post['client_address'] ?? null,
                'subtotal'       => $post['subtotal'] !== '' ? (float) $post['subtotal'] : null,
                'tax_rate'       => $post['tax_rate'] !== '' ? (float) $post['tax_rate'] : null,
                'tax_amount'     => $post['tax_amount'] !== '' ? (float) $post['tax_amount'] : null,
                'total_amount'   => $post['total_amount'] !== '' ? (float) $post['total_amount'] : null,
                'currency'       => $post['currency'] ?? 'TND',
                'payment_method' => $post['payment_method'] ?? null,
                'notes'          => $post['notes'] ?? null,
                'raw_text'       => $container->rawText ?? '',
                'source_file'    => $container->sourceFile ?? '',
                'items'          => $items,
            ];

            try {
                $invoiceId = $this->apiClient->createInvoice(
                    $invoiceData,
                    createdBy: (int) $this->currentUser['user_id'],
                );
                unset($container->data, $container->rawText, $container->sourceFile);

                return $this->redirect()->toRoute('invoice-view', ['id' => $invoiceId]);
            } catch (\RuntimeException $e) {
                return new ViewModel([
                    'data'        => $invoiceData,
                    'error'       => $e->getMessage(),
                    'currentUser' => $this->currentUser,
                ]);
            }
        }

        // GET : afficher les données extraites en session
        if (empty($container->data)) {
            return $this->redirect()->toRoute('invoice-upload');
        }

        return new ViewModel([
            'data'        => $container->data,
            'currentUser' => $this->currentUser,
        ]);
    }

    // -----------------------------------------------------------------
    // History : liste paginée des factures
    // - admin    → toutes les factures
    // - employee → uniquement les siennes
    // -----------------------------------------------------------------
    public function historyAction(): ViewModel
    {
        $page  = (int) ($this->params()->fromQuery('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        try {
            $result = $this->apiClient->listInvoices(
                limit: $limit,
                offset: $offset,
                userId: (int) $this->currentUser['user_id'],
                role: $this->currentUser['role'],
            );
        } catch (\RuntimeException $e) {
            return new ViewModel([
                'error'       => $e->getMessage(),
                'invoices'    => [],
                'count'       => 0,
                'currentUser' => $this->currentUser,
            ]);
        }

        return new ViewModel([
            'invoices'    => $result['results'],
            'count'       => $result['count'],
            'page'        => $page,
            'limit'       => $limit,
            'currentUser' => $this->currentUser,
        ]);
    }

    // -----------------------------------------------------------------
    // View : détail d'une facture
    // - admin    → peut consulter n'importe quelle facture
    // - employee → uniquement les siennes (sinon "introuvable")
    // -----------------------------------------------------------------
    public function viewAction(): ViewModel
    {
        $id = (int) $this->params()->fromRoute('id');

        try {
            $invoice = $this->apiClient->getInvoice(
                $id,
                userId: (int) $this->currentUser['user_id'],
                role: $this->currentUser['role'],
            );
        } catch (\RuntimeException $e) {
            return new ViewModel(['error'=>$e->getMessage(),'currentUser'=>$this->currentUser]);
        }

        if ($invoice === null) {
            return new ViewModel(['error'=>"Facture #{$id} introuvable.",'currentUser'=>$this->currentUser]);
        }

        return new ViewModel(['invoice'=>$invoice,'currentUser'=>$this->currentUser]);
    }

    // -----------------------------------------------------------------
    // Search : recherche intelligente (formulaire + résultats)
    // -----------------------------------------------------------------
    public function searchAction(): ViewModel
    {
        $query = trim((string) $this->params()->fromQuery('q', ''));

        if ($query === '') {
            return new ViewModel([
                'currentUser' => $this->currentUser,
            ]);
        }

        try {
            $result = $this->apiClient->search(
                $query,
                $this->currentUser['licence_key'],
                userId: (int) $this->currentUser['user_id'],
                role: $this->currentUser['role'],
            );
        } catch (\RuntimeException $e) {
            return new ViewModel([
                'query'       => $query,
                'error'       => $e->getMessage(),
                'currentUser' => $this->currentUser,
            ]);
        }

        return new ViewModel([
            'query'       => $query,
            'result'      => $result,
            'currentUser' => $this->currentUser,
        ]);
    }
}