<?php
$host = '127.0.0.1';
$db   = 'erp_client_db';
$dbUser = 'root';
$dbPass = 'imenemnamariam123';

$username      = 'admin';
$email         = 'admin@erp-ai.local';
$plainPassword = 'Admin@2024';
$fullname      = 'Administrateur ERP-AI';
$role          = 'admin';
$licenceKey    = 'ERP-AI-2024-DEV-ZZZZ';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, fullname, role, licence_key)
        VALUES (:username, :email, :password_hash, :fullname, :role, :licence_key)
    ");

    $stmt->execute([
        'username'      => $username,
        'email'         => $email,
        'password_hash' => $passwordHash,
        'fullname'      => $fullname,
        'role'          => $role,
        'licence_key'   => $licenceKey,
    ]);

    echo "✅ Compte ADMIN créé avec succès !\n";
    echo "   Username : {$username}\n";
    echo "   Password : {$plainPassword}\n";

} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}