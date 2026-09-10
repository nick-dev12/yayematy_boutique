<?php
/**
 * Simule les notifications commande (sans créer de commande réelle)
 * Usage : php scripts/test_commande_notifications_cli.php [user_id]
 */
require __DIR__ . '/../services/firebase_push.php';
require __DIR__ . '/../models/model_fcm.php';

$user_id = isset($argv[1]) ? (int) $argv[1] : 0;

echo "=== Test notifications commande (simulation) ===\n";

$admin_tokens = get_all_fcm_tokens_admin();
echo 'Tokens admin enregistrés : ' . count($admin_tokens) . "\n";

if (!empty($admin_tokens)) {
    $r = firebase_send_notification(
        $admin_tokens,
        'Test admin — nouvelle commande',
        'Simulation CMD-TEST — 15 000 FCFA (2 articles)',
        ['link' => '/admin/commandes/index.php', 'tag' => 'test-cmd-admin']
    );
    echo 'Envoi admin : success=' . ($r['success'] ?? 0) . ' failed=' . ($r['failed'] ?? 0);
    if (!empty($r['errors'])) {
        echo ' errors=' . implode(' | ', $r['errors']);
    }
    echo "\n";
} else {
    echo "Aucun token admin — activez les notifications dans l'admin.\n";
}

if ($user_id > 0) {
    $user_tokens = get_fcm_tokens_by_user($user_id);
    echo 'Tokens client user_id=' . $user_id . ' : ' . count($user_tokens) . "\n";
    if (!empty($user_tokens)) {
        $r2 = firebase_send_notification(
            $user_tokens,
            'Test client — commande confirmée',
            'Simulation CMD-TEST enregistrée — 15 000 FCFA',
            ['link' => '/user/mes-commandes.php', 'tag' => 'test-cmd-client']
        );
        echo 'Envoi client : success=' . ($r2['success'] ?? 0) . ' failed=' . ($r2['failed'] ?? 0);
        if (!empty($r2['errors'])) {
            echo ' errors=' . implode(' | ', $r2['errors']);
        }
        echo "\n";

        $r3 = firebase_send_notification(
            $user_tokens,
            'Test client — mise à jour statut',
            'Commande #CMD-TEST : En préparation',
            ['link' => '/user/mes-commandes.php', 'statut' => 'en_preparation', 'tag' => 'test-statut-client']
        );
        echo 'Envoi statut : success=' . ($r3['success'] ?? 0) . ' failed=' . ($r3['failed'] ?? 0);
        if (!empty($r3['errors'])) {
            echo ' errors=' . implode(' | ', $r3['errors']);
        }
        echo "\n";
    } else {
        echo "Aucun token client pour user_id={$user_id} — activez les notifications sur Mon compte.\n";
    }
} else {
    echo "Passez un user_id en argument pour tester le client (ex: php scripts/test_commande_notifications_cli.php 1)\n";
}
