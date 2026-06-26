<?php
/**
 * create_user.php
 * ----------------
 * Script à exécuter UNE FOIS pour créer le premier compte client
 * dans erp_client_db. À lancer en ligne de commande :
 *
 *   php create_user.php
 *
 * Tu peux le supprimer après usage, ou le garder pour créer d'autres
 * comptes en changeant les variables ci-dessous.
 */

// ── Connexion à erp_client_db ──────────────────────────────────────────
$host = '127.0.0.1';
$db   = 'erp_client_db';
$dbUser = 'root';
$dbPass = 'imenemnamariam123';   // ← adapte si besoin

// ── Informations du compte à créer ─────────────────────────────────────
$username    = 'techstore';
$email       = 'contact@techstore.tn';
$plainPassword = 'Client@2026';
$fullname    = 'Tech Store Tunisia';
$licenceKey  = 'ERP-AI-2024-DEV-ZZZZ';   // doit exister dans invoices_db.licences

// ------------------------------------------------------------------------

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, fullname, licence_key)
        VALUES (:username, :email, :password_hash, :fullname, :licence_key)
    ");

    $stmt->execute([
        'username'      => $username,
        'email'         => $email,
        'password_hash' => $passwordHash,
        'fullname'      => $fullname,
        'licence_key'   => $licenceKey,
    ]);

    echo "✅ Compte créé avec succès !\n";
    echo "   Username : {$username}\n";
    echo "   Password : {$plainPassword}\n";
    echo "   Licence  : {$licenceKey}\n";

} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}