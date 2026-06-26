<?php

declare(strict_types=1);

namespace Invoice\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Container as SessionContainer;
use PDO;

/**
 * UserController
 * ---------------
 * CRUD complet des utilisateurs de erp_client_db.users.
 * Accès strictement réservé au rôle 'admin' — vérifié dans onDispatch().
 */
class UserController extends AbstractActionController
{
    private ?array $currentUser = null;

    public function __construct(
        private string $dbHost,
        private string $dbName,
        private string $dbUser,
        private string $dbPass
    ) {
    }

    private function getPdo(): PDO
    {
        $pdo = new PDO(
            "mysql:host={$this->dbHost};dbname={$this->dbName};charset=utf8mb4",
            $this->dbUser,
            $this->dbPass
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    /**
     * Vérifie l'authentification ET le rôle admin avant chaque action.
     */
    public function onDispatch(MvcEvent $event)
    {
        $session = new SessionContainer('erp_user');

        if (empty($session->user_id)) {
            return $this->redirect()->toRoute('login');
        }

        if (($session->role ?? 'employee') !== 'admin') {
            $response = $this->getResponse();
            $response->setStatusCode(403);
            $response->setContent(
                '<h1>403 - Accès refusé</h1><p>Cette page est réservée aux administrateurs.</p>'
                . '<a href="' . $this->url()->fromRoute('invoice') . '">Retour au dashboard</a>'
            );
            return $response;
        }

        $this->currentUser = [
            'user_id'  => $session->user_id,
            'username' => $session->username,
            'fullname' => $session->fullname,
            'role'     => $session->role,
        ];

        $event->getViewModel()->setVariable('currentUser', $this->currentUser);

        return parent::onDispatch($event);
    }

    // -----------------------------------------------------------------
    // Liste des utilisateurs
    // -----------------------------------------------------------------
    public function indexAction(): ViewModel
    {
        $pdo = $this->getPdo();
        $stmt = $pdo->query('SELECT id, username, email, fullname, role, licence_key, is_active, last_login, created_at FROM users ORDER BY created_at DESC');
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return new ViewModel(['users' => $users]);
    }

    // -----------------------------------------------------------------
    // Créer un utilisateur (GET affiche le formulaire, POST l'enregistre)
    // -----------------------------------------------------------------
    public function createAction(): ViewModel
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $post = $request->getPost();

            $username    = trim((string) $post->get('username', ''));
            $email       = trim((string) $post->get('email', ''));
            $password    = (string) $post->get('password', '');
            $fullname    = trim((string) $post->get('fullname', ''));
            $role        = $post->get('role', 'employee') === 'admin' ? 'admin' : 'employee';
            $licenceKey  = trim((string) $post->get('licence_key', ''));

            if ($username === '' || $email === '' || $password === '' || $licenceKey === '') {
                return new ViewModel(['error' => 'Tous les champs obligatoires doivent être remplis.']);
            }

            try {
                $pdo = $this->getPdo();
                $stmt = $pdo->prepare('
                    INSERT INTO users (username, email, password_hash, fullname, role, licence_key)
                    VALUES (:username, :email, :password_hash, :fullname, :role, :licence_key)
                ');
                $stmt->execute([
                    'username'      => $username,
                    'email'         => $email,
                    'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                    'fullname'      => $fullname,
                    'role'          => $role,
                    'licence_key'   => $licenceKey,
                ]);

                return $this->redirect()->toRoute('users');

            } catch (\PDOException $e) {
                $message = str_contains($e->getMessage(), 'Duplicate')
                    ? 'Ce nom d\'utilisateur ou cet email existe déjà.'
                    : 'Erreur lors de la création.';
                return new ViewModel(['error' => $message]);
            }
        }

        return new ViewModel();
    }

    // -----------------------------------------------------------------
    // Modifier un utilisateur
    // -----------------------------------------------------------------
    public function editAction(): ViewModel
    {
        $id = (int) $this->params()->fromRoute('id');
        $pdo = $this->getPdo();

        $request = $this->getRequest();

        if ($request->isPost()) {
            $post = $request->getPost();

            $fullname   = trim((string) $post->get('fullname', ''));
            $email      = trim((string) $post->get('email', ''));
            $role       = $post->get('role', 'employee') === 'admin' ? 'admin' : 'employee';
            $licenceKey = trim((string) $post->get('licence_key', ''));
            $isActive   = $post->get('is_active') ? 1 : 0;
            $newPassword = (string) $post->get('password', '');

            try {
                if ($newPassword !== '') {
                    $stmt = $pdo->prepare('
                        UPDATE users SET email = :email, fullname = :fullname, role = :role,
                            licence_key = :licence_key, is_active = :is_active, password_hash = :password_hash
                        WHERE id = :id
                    ');
                    $stmt->execute([
                        'email'         => $email,
                        'fullname'      => $fullname,
                        'role'          => $role,
                        'licence_key'   => $licenceKey,
                        'is_active'     => $isActive,
                        'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
                        'id'            => $id,
                    ]);
                } else {
                    $stmt = $pdo->prepare('
                        UPDATE users SET email = :email, fullname = :fullname, role = :role,
                            licence_key = :licence_key, is_active = :is_active
                        WHERE id = :id
                    ');
                    $stmt->execute([
                        'email'       => $email,
                        'fullname'    => $fullname,
                        'role'        => $role,
                        'licence_key' => $licenceKey,
                        'is_active'   => $isActive,
                        'id'          => $id,
                    ]);
                }

                return $this->redirect()->toRoute('users');

            } catch (\PDOException $e) {
                return new ViewModel(['error' => 'Erreur lors de la mise à jour.']);
            }
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return new ViewModel(['error' => 'Utilisateur introuvable.']);
        }

        return new ViewModel(['user' => $user]);
    }

    // -----------------------------------------------------------------
    // Supprimer un utilisateur
    // -----------------------------------------------------------------
    public function deleteAction(): \Laminas\Http\Response
    {
        $id = (int) $this->params()->fromRoute('id');

        // Empêche un admin de se supprimer lui-même par erreur
        if ($id === (int) $this->currentUser['user_id']) {
            return $this->redirect()->toRoute('users');
        }

        $pdo = $this->getPdo();
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $this->redirect()->toRoute('users');
    }
}