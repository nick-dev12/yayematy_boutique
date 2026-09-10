<?php
/**
 * Scripts Firebase notifications — à inclure en fin de page (avant </body>)
 * Variables attendues :
 *   $enable_firebase_notifications (bool)
 *   $firebase_notify_type (string, optionnel : user|admin, défaut user)
 */
require_once __DIR__ . '/firebase_config_loader.php';

if (empty($enable_firebase_notifications)) {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (!empty($_SESSION['admin_id'])) {
        $enable_firebase_notifications = true;
        if (!isset($firebase_notify_type)) {
            $firebase_notify_type = 'admin';
        }
    } elseif (!empty($_SESSION['user_id'])) {
        $enable_firebase_notifications = true;
        if (!isset($firebase_notify_type)) {
            $firebase_notify_type = 'user';
        }
    }
}
if (empty($enable_firebase_notifications)) {
    return;
}

if (!firebase_config_is_available()) {
    ?>
<script>console.info('[FCM] Notifications désactivées — copiez config/firebase_config.example.php vers config/firebase_config.php');</script>
    <?php
    return;
}

$firebase_notify_type = isset($firebase_notify_type) && $firebase_notify_type === 'admin' ? 'admin' : 'user';
require_once __DIR__ . '/asset_version.php';
$firebase_js_path = __DIR__ . '/../js/firebase-notifications.js';
$firebase_js_v = file_exists($firebase_js_path) ? (string) filemtime($firebase_js_path) : get_asset_version();
$firebase_js_url = public_url('/js/firebase-notifications.js') . '?v=' . rawurlencode($firebase_js_v);
?>
<script>console.log('[FCM] Chargement des scripts notifications…');</script>
<script src="https://www.gstatic.com/firebasejs/12.9.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/12.9.0/firebase-messaging-compat.js"></script>
<?php require_once __DIR__ . '/firebase_init.php'; ?>
<script>
    if (window.FIREBASE_CONFIG && typeof firebase !== 'undefined') {
        try {
            var _fcmAppConfig = {
                apiKey: window.FIREBASE_CONFIG.apiKey,
                authDomain: window.FIREBASE_CONFIG.authDomain,
                projectId: window.FIREBASE_CONFIG.projectId,
                storageBucket: window.FIREBASE_CONFIG.storageBucket,
                messagingSenderId: window.FIREBASE_CONFIG.messagingSenderId,
                appId: window.FIREBASE_CONFIG.appId
            };
            if (window.FIREBASE_CONFIG.measurementId) {
                _fcmAppConfig.measurementId = window.FIREBASE_CONFIG.measurementId;
            }
            firebase.initializeApp(_fcmAppConfig);
            console.log('[FCM] Firebase initialisé (projet:', _fcmAppConfig.projectId + ')');
        } catch (e) {
            if (!String(e.message || e).includes('already exists')) {
                console.error('[FCM] Firebase init:', e);
            }
        }
    } else {
        console.warn('[FCM] Firebase ou FIREBASE_CONFIG manquant');
    }
    window.FIREBASE_NOTIFY_TYPE = <?php echo json_encode($firebase_notify_type); ?>;
</script>
<script src="<?php echo htmlspecialchars($firebase_js_url, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script>
    if (typeof window.FirebaseNotifications === 'undefined') {
        console.warn('[FCM] firebase-notifications.js introuvable ou en erreur — vérifiez l’onglet Réseau (F12)');
    }
</script>
