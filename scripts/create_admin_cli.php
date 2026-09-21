<?php
/**
 * Création / mise à jour d'un compte admin — php scripts/create_admin_cli.php
 */
require __DIR__ . '/../conn/conn.php';
require __DIR__ . '/../models/model_admin.php';

if (!db_is_available()) {
    fwrite(STDERR, "Base de données indisponible.\n");
    exit(1);
}

$email = 'admin@yayematy.com';
$password = 'Y@yeMaty2026!Admin';
$hash = password_hash($password, PASSWORD_BCRYPT);

if (admin_email_exists($email)) {
    global $db;
    $stmt = $db->prepare('UPDATE admin SET password = :p, role = :r, statut = :s WHERE email = :e');
    $ok = $stmt->execute([
        'p' => $hash,
        'r' => 'admin',
        's' => 'actif',
        'e' => $email,
    ]);
    echo $ok ? "UPDATED\n" : "UPDATE_FAILED\n";
    exit($ok ? 0 : 1);
}

$id = create_admin('Admin', 'YayeMaty', $email, $hash, 'admin');
if (!$id) {
    fwrite(STDERR, "Échec création admin.\n");
    exit(1);
}

echo "CREATED id={$id}\n";
