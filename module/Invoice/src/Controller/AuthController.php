<?php

declare(strict_types=1);

namespace Invoice\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Container as SessionContainer;
use PDO;

/**
 * AuthController
 * ---------------
 * Authentification LOCALE du client, indépendante de Django.
 * Vérifie username/password directement dans erp_client_db.users.
 * Stocke la session Laminas (user_id, username, licence_key).
 */
class AuthController extends AbstractActionController
{
    public function __construct(
        private string $dbHost,
        private string $dbName,
        private string $dbUser,
        private string $dbPass
    ) {
    }

    private function getPdo(): PDO
    {
        return new PDO(
            "mysql:host={$this->dbHost};dbname={$this->dbName};charset=utf8mb4",
            $this->dbUser,
            $this->dbPass
        );
    }

    public function loginAction(): ViewModel
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $username = trim((string) $this->params()->fromPost('username', ''));
            $password = (string) $this->params()->fromPost('password', '');

            if ($username === '' || $password === '') {
                return new ViewModel(['error' => 'Merci de remplir tous les champs.']);
            }

            try {
                $pdo = $this->getPdo();
                $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username AND is_active = 1');
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user || !password_verify($password, $user['password_hash'])) {
                    return new ViewModel(['error' => 'Identifiants incorrects.']);
                }

                // Authentification réussie → on crée la session Laminas locale
                $session = new SessionContainer('erp_user');
                $session->user_id     = $user['id'];
                $session->username    = $user['username'];
                $session->fullname    = $user['fullname'];
                $session->licence_key = $user['licence_key'];
                $session->role        = $user['role'];

                // Met à jour last_login
                $update = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
                $update->execute(['id' => $user['id']]);

                return $this->redirect()->toRoute('invoice');

            } catch (\PDOException $e) {
                return new ViewModel(['error' => 'Erreur de connexion à la base de données.']);
            }
        }

        // Si déjà connecté, redirige directement vers le dashboard
        $session = new SessionContainer('erp_user');
        if (!empty($session->user_id)) {
            return $this->redirect()->toRoute('invoice');
        }

        return new ViewModel();
    }

    public function logoutAction(): \Laminas\Http\Response
    {
        $session = new SessionContainer('erp_user');
        $session->getManager()->getStorage()->clear('erp_user');

        return $this->redirect()->toRoute('login');
    }
}