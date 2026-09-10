<?php
require __DIR__ . '/../conn/conn.php';
if (!$db) {
    echo "NO_DB\n";
    exit(1);
}
$table = $db->query("SHOW TABLES LIKE 'fcm_tokens'");
if (!$table || !$table->fetch()) {
    echo "NO_TABLE\n";
    exit(1);
}
echo "=== fcm_tokens stats ===\n";
foreach ($db->query("SELECT type, COUNT(*) AS total, SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) AS with_user, SUM(CASE WHEN admin_id IS NOT NULL THEN 1 ELSE 0 END) AS with_admin FROM fcm_tokens GROUP BY type") as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
}
echo "=== sample tokens ===\n";
foreach ($db->query("SELECT id, type, user_id, admin_id, LEFT(token,24) AS token_prefix, date_creation FROM fcm_tokens ORDER BY id DESC LIMIT 5") as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
}
