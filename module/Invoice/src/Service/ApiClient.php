<?php

declare(strict_types=1);

namespace Invoice\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * ApiClient
 * ---------
 * Encapsule tous les appels HTTP vers l'API Python (FastAPI) qui expose
 * les agents d'extraction et de recherche de factures.
 *
 * Chaque méthode retourne un tableau associatif PHP (JSON décodé),
 * ou lève une exception RuntimeException avec un message clair en cas d'erreur.
 */
class ApiClient
{
    private Client $httpClient;
    private string $baseUrl;

    public function __construct(string $baseUrl, int $timeout = 60)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => $timeout,
        ]);
    }

    /**
     * Vérifie que l'API Python est accessible.
     */
    public function healthCheck(): bool
    {
        try {
            $response = $this->httpClient->get('/api/health');
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            return false;
        }
    }

    /**
     * Envoie un fichier (PDF/image) à l'API pour extraction + analyse IA.
     * Ne sauvegarde rien en base — retourne les données brutes extraites.
     *
     * @param string $filePath Chemin absolu du fichier temporaire uploadé
     * @param string $fileName Nom original du fichier
     * @return array Données extraites (invoice_number, vendor_name, items, etc.)
     * @throws \RuntimeException si l'extraction échoue
     */
    public function extractInvoice(string $filePath, string $fileName, string $licenceKey): array
    {
        try {
            $response = $this->httpClient->post('/api/extract', [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($filePath, 'rb'),
                        'filename' => $fileName,
                    ],
                    [
                        'name'     => 'licence_key',
                        'contents' => $licenceKey,
                    ],
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            return $body ?? [];

        } catch (GuzzleException $e) {
            $message = $this->extractErrorMessage($e);
            throw new \RuntimeException("Erreur lors de l'extraction : {$message}");
        }
    }

    /**
     * Sauvegarde une facture validée par l'utilisateur en base de données.
     *
     * @param array $invoiceData Données du formulaire (déjà validées/corrigées)
     * @return int L'ID de la facture créée
     * @throws \RuntimeException si la sauvegarde échoue
     */
    public function createInvoice(array $invoiceData): int
    {
        try {
            $response = $this->httpClient->post('/api/invoices', [
                'json' => $invoiceData,
            ]);

            $body = json_decode((string) $response->getBody(), true);
            return (int) ($body['invoice_id'] ?? 0);

        } catch (GuzzleException $e) {
            $message = $this->extractErrorMessage($e);
            throw new \RuntimeException("Erreur lors de la sauvegarde : {$message}");
        }
    }

    /**
     * Liste paginée des factures (pour Dashboard et Historique).
     *
     * @return array{count: int, results: array[]}
     */
    public function listInvoices(int $limit = 50, int $offset = 0): array
    {
        try {
            $response = $this->httpClient->get('/api/invoices', [
                'query' => ['limit' => $limit, 'offset' => $offset],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            return [
                'count'   => $body['count'] ?? 0,
                'results' => $body['results'] ?? [],
            ];

        } catch (GuzzleException $e) {
            $message = $this->extractErrorMessage($e);
            throw new \RuntimeException("Erreur lors de la lecture des factures : {$message}");
        }
    }

    /**
     * Détail complet d'une facture (avec ses lignes d'articles).
     *
     * @return array|null null si la facture n'existe pas
     */
    public function getInvoice(int $invoiceId): ?array
    {
        try {
            $response = $this->httpClient->get("/api/invoices/{$invoiceId}");
            $body = json_decode((string) $response->getBody(), true);
            return $body['invoice'] ?? null;

        } catch (GuzzleException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
            $message = $this->extractErrorMessage($e);
            throw new \RuntimeException("Erreur lors de la lecture de la facture : {$message}");
        }
    }

    /**
     * Lance une recherche intelligente sur les factures.
     *
     * @return array{intent: string, strategy: string, count: int, results: array[], natural_response: string}
     */
    public function search(string $query, string $licenceKey): array
    {
        try {
            $response = $this->httpClient->post('/api/search', [
                'json' => ['query' => $query, 'licence_key' => $licenceKey],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            return $body ?? [];

        } catch (GuzzleException $e) {
            $message = $this->extractErrorMessage($e);
            throw new \RuntimeException("Erreur lors de la recherche : {$message}");
        }
    }

    /**
     * Extrait un message d'erreur lisible depuis une exception Guzzle,
     * en essayant de lire le champ 'detail' renvoyé par FastAPI.
     */
    private function extractErrorMessage(GuzzleException $e): string
    {
        if (method_exists($e, 'getResponse') && $e->getResponse() !== null) {
            $body = json_decode((string) $e->getResponse()->getBody(), true);
            if (isset($body['detail'])) {
                return $body['detail'];
            }
        }
        return $e->getMessage();
    }
}