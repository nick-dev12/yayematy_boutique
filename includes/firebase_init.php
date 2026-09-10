<?php
/**
 * Configuration Firebase - Source unique pour toutes les pages
 * En cas d'erreur "API key not valid", voir FIX_API_KEY_NOTIFICATIONS.md
 */
require_once __DIR__ . '/firebase_config_loader.php';

if (!function_exists('public_url')) {
    require_once __DIR__ . '/site_url.php';
}

$firebase_config = firebase_load_config();
$fcm_sw_path = public_url('/firebase-messaging-sw.js');

if ($firebase_config === null) {
    ?>
<script>
    window.FIREBASE_CONFIG = null;
    window.FIREBASE_VAPID_KEY = '';
    window.FCM_SW_PATH = <?php echo json_encode($fcm_sw_path); ?>;
</script>
    <?php
    return;
}
?>
<script>
    window.FIREBASE_CONFIG = <?php echo json_encode([
        'apiKey' => $firebase_config['apiKey'] ?? '',
        'authDomain' => $firebase_config['authDomain'] ?? '',
        'projectId' => $firebase_config['projectId'] ?? '',
        'storageBucket' => $firebase_config['storageBucket'] ?? '',
        'messagingSenderId' => $firebase_config['messagingSenderId'] ?? '',
        'appId' => $firebase_config['appId'] ?? '',
        'measurementId' => $firebase_config['measurementId'] ?? null,
    ]); ?>;
    window.FIREBASE_VAPID_KEY = <?php echo json_encode(trim((string) ($firebase_config['vapidKey'] ?? ''))); ?>;
    window.FCM_SW_PATH = <?php echo json_encode($fcm_sw_path); ?>;
</script>
