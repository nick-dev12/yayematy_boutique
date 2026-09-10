<?php
require_once __DIR__ . '/../includes/session_user.php';
/**
 * Page de test Firebase (web) — configuration, FCM, service worker
 */
session_start_persistent();

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
    header('Location: login.php');
    exit;
}

$enable_firebase_notifications = true;
$firebase_notify_type = 'admin';

$config_path = __DIR__ . '/../config/firebase_config.php';
$server_config_path = __DIR__ . '/../config/firebase_server.php';
$sw_path = __DIR__ . '/../firebase-messaging-sw.js';

$firebase_config = file_exists($config_path) ? require $config_path : [];
$server_config = file_exists($server_config_path) ? require $server_config_path : [];
$credentials_path = $server_config['credentials_path'] ?? (__DIR__ . '/../yaye-bc53c-a6d9d59ae615.json');

require_once __DIR__ . '/../models/model_fcm.php';
require_once __DIR__ . '/../services/firebase_push.php';

$message = '';
$message_type = '';

function firebase_test_mask_key($value, $visible = 8)
{
    $value = (string) $value;
    if ($value === '') {
        return '—';
    }
    if (strlen($value) <= $visible * 2) {
        return htmlspecialchars($value);
    }
    return htmlspecialchars(substr($value, 0, $visible) . '…' . substr($value, -$visible));
}

function firebase_test_status_row($label, $ok, $detail = '')
{
    $icon = $ok ? 'fa-check-circle' : 'fa-times-circle';
    $class = $ok ? 'ok' : 'ko';
    echo '<tr class="status-row ' . $class . '">';
    echo '<td><i class="fas ' . $icon . '"></i> ' . htmlspecialchars($label) . '</td>';
    echo '<td>' . ($detail !== '' ? htmlspecialchars($detail) : ($ok ? 'OK' : 'Échec')) . '</td>';
    echo '</tr>';
}

$checks = [];
$checks['config_file'] = file_exists($config_path);
$checks['project_id'] = ($firebase_config['projectId'] ?? '') === 'yaye-bc53c';
$checks['vapid_key'] = !empty($firebase_config['vapidKey']);
$checks['vapid_length'] = strlen(trim($firebase_config['vapidKey'] ?? '')) === 87;
$checks['server_config'] = file_exists($server_config_path);
$checks['credentials_file'] = file_exists($credentials_path);
$checks['sw_file'] = file_exists($sw_path);

$access_token = null;
$access_token_error = '';
if ($checks['credentials_file']) {
    $access_token = firebase_get_access_token($credentials_path);
    if (!$access_token) {
        $access_token_error = 'Impossible d\'obtenir un token OAuth (vérifiez le fichier service account et cacert.pem).';
    }
}
$checks['access_token'] = !empty($access_token);

$sw_content = $checks['sw_file'] ? file_get_contents($sw_path) : '';
$checks['sw_project'] = $sw_content !== '' && strpos($sw_content, 'yaye-bc53c') !== false;

$admin_tokens = get_fcm_tokens_by_admin((int) $_SESSION['admin_id']);
$checks['admin_tokens'] = !empty($admin_tokens);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_test') {
    if (empty($admin_tokens)) {
        $message = 'Aucun token FCM enregistré pour votre compte admin. Activez d\'abord les notifications ci-dessous.';
        $message_type = 'error';
    } elseif (!$checks['access_token']) {
        $message = $access_token_error ?: 'Service account non configuré.';
        $message_type = 'error';
    } else {
        $result = firebase_send_notification(
            $admin_tokens,
            'Test YAYEMATY Firebase',
            'Notification de test envoyée depuis la page admin Firebase.',
            ['link' => '/admin/firebase-test.php', 'tag' => 'firebase-test']
        );
        if ($result['success'] > 0) {
            $message = 'Notification envoyée avec succès à ' . $result['success'] . ' appareil(s).';
            $message_type = 'success';
        } else {
            $message = 'Échec envoi FCM : ' . implode(' | ', $result['errors'] ?? ['Erreur inconnue']);
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Firebase - Admin</title>
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <style>
        .firebase-test-grid { display: grid; gap: 1.25rem; max-width: 52rem; }
        .detail-box table { width: 100%; border-collapse: collapse; font-size: 0.9375rem; }
        .detail-box td { padding: 0.625rem 0.75rem; border-bottom: 1px solid rgba(0,0,0,0.06); vertical-align: top; }
        .detail-box td:first-child { font-weight: 600; width: 42%; color: var(--color-noir, #1A1A1A); }
        .status-row.ok td:first-child i { color: var(--color-bleu, #2E7DB5); }
        .status-row.ko td:first-child i { color: #c62828; }
        .message { padding: 1rem 1.25rem; border-radius: 0.625rem; margin: 1rem 0; font-size: 1rem; }
        .message.success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; }
        .message.error { background: #ffebee; color: #c62828; border-left: 4px solid #c62828; }
        .firebase-test-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1rem; }
        .btn-firebase {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.75rem 1.25rem; border: none; border-radius: 0.5rem;
            background: var(--color-orange, #F25C19); color: #fff;
            font-size: 1rem; cursor: pointer; text-decoration: none;
        }
        .btn-firebase:hover { opacity: 0.92; }
        .btn-firebase--secondary { background: var(--color-bleu, #2E7DB5); }
        #client-test-log {
            margin-top: 1rem; padding: 1rem; background: #f5f5f5; border-radius: 0.5rem;
            font-family: Consolas, monospace; font-size: 0.875rem; white-space: pre-wrap;
            max-height: 16rem; overflow-y: auto;
        }
        .client-test-item { margin-bottom: 0.5rem; }
        .client-test-item.ok { color: #2e7d32; }
        .client-test-item.ko { color: #c62828; }
        .client-test-item.pending { color: var(--color-gris, #8C8C8C); }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>
    <div class="contents-container">
        <div class="content-header">
            <h1><i class="fas fa-fire"></i> Test Firebase (web)</h1>
            <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>

        <p>Vérification du projet <strong>yaye-bc53c</strong> — notifications push, service worker et envoi serveur.</p>

        <?php if ($message !== ''): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="firebase-test-grid">
            <div class="detail-box">
                <h3><i class="fas fa-server"></i> Vérifications serveur (PHP)</h3>
                <table>
                    <?php
                    firebase_test_status_row('Fichier config/firebase_config.php', $checks['config_file']);
                    firebase_test_status_row('Projet Firebase = yaye-bc53c', $checks['project_id'], $firebase_config['projectId'] ?? '—');
                    firebase_test_status_row('Clé VAPID présente', $checks['vapid_key']);
                    firebase_test_status_row('Clé VAPID (87 caractères)', $checks['vapid_length'], strlen(trim($firebase_config['vapidKey'] ?? '')) . ' car.');
                    firebase_test_status_row('Fichier config/firebase_server.php', $checks['server_config']);
                    firebase_test_status_row('Service account JSON', $checks['credentials_file'], basename($credentials_path));
                    firebase_test_status_row('Token OAuth FCM (serveur)', $checks['access_token'], $checks['access_token'] ? 'Token obtenu' : $access_token_error);
                    firebase_test_status_row('Service Worker synchronisé', $checks['sw_project'], $checks['sw_file'] ? 'firebase-messaging-sw.js' : 'Fichier absent');
                    firebase_test_status_row('Tokens FCM admin (vous)', $checks['admin_tokens'], count($admin_tokens) . ' token(s)');
                    ?>
                </table>
            </div>

            <div class="detail-box">
                <h3><i class="fas fa-cog"></i> Configuration web actuelle</h3>
                <table>
                    <tr><td>apiKey</td><td><?php echo firebase_test_mask_key($firebase_config['apiKey'] ?? ''); ?></td></tr>
                    <tr><td>authDomain</td><td><?php echo htmlspecialchars($firebase_config['authDomain'] ?? '—'); ?></td></tr>
                    <tr><td>projectId</td><td><?php echo htmlspecialchars($firebase_config['projectId'] ?? '—'); ?></td></tr>
                    <tr><td>messagingSenderId</td><td><?php echo htmlspecialchars($firebase_config['messagingSenderId'] ?? '—'); ?></td></tr>
                    <tr><td>appId</td><td><?php echo firebase_test_mask_key($firebase_config['appId'] ?? '', 12); ?></td></tr>
                    <tr><td>measurementId</td><td><?php echo htmlspecialchars($firebase_config['measurementId'] ?? '—'); ?></td></tr>
                    <tr><td>vapidKey</td><td><?php echo firebase_test_mask_key($firebase_config['vapidKey'] ?? '', 10); ?></td></tr>
                </table>
            </div>

            <div class="detail-box">
                <h3><i class="fas fa-desktop"></i> Tests navigateur</h3>
                <p style="font-size: 1rem; margin-bottom: 0.75rem;">Cliquez sur « Activer les notifications » dans le menu latéral, puis lancez les tests client.</p>
                <div class="firebase-test-actions">
                    <button type="button" class="btn-firebase btn-firebase--secondary" id="btn-run-client-tests">
                        <i class="fas fa-play"></i> Lancer les tests client
                    </button>
                    <form method="post" action="firebase-test.php" style="display:inline;">
                        <input type="hidden" name="action" value="send_test">
                        <button type="submit" class="btn-firebase" <?php echo $checks['access_token'] ? '' : 'disabled'; ?>>
                            <i class="fas fa-paper-plane"></i> Envoyer notification test
                        </button>
                    </form>
                </div>
                <div id="client-test-log" aria-live="polite"></div>
            </div>

            <div class="detail-box">
                <h3><i class="fas fa-info-circle"></i> Configurations manquantes pour le web</h3>
                <ul style="font-size: 1rem; line-height: 1.7; padding-left: 1.25rem;">
                    <li><strong>Auth Google</strong> : provider activé dans Firebase Console (domaines : localhost, yayematy.com).</li>
                    <li><strong>Google Cloud</strong> : autoriser la clé API pour <code>http://localhost:5000/*</code> et votre domaine de production.</li>
                    <li><strong>Analytics</strong> : measurementId présent ; vérifiez dans Firebase Console que Google Analytics est activé.</li>
                </ul>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    (function () {
        var logEl = document.getElementById('client-test-log');
        var btn = document.getElementById('btn-run-client-tests');

        function logLine(text, state) {
            var div = document.createElement('div');
            div.className = 'client-test-item ' + (state || 'pending');
            div.textContent = text;
            logEl.appendChild(div);
        }

        function clearLog() {
            logEl.innerHTML = '';
        }

        function runClientTests() {
            clearLog();
            logLine('Démarrage des tests client…', 'pending');

            if (!window.isSecureContext) {
                logLine('✗ Contexte non sécurisé (HTTPS ou localhost requis)', 'ko');
                return;
            }
            logLine('✓ Contexte sécurisé', 'ok');

            if (typeof firebase === 'undefined') {
                logLine('✗ SDK Firebase non chargé', 'ko');
                return;
            }
            logLine('✓ SDK Firebase chargé', 'ok');

            if (!window.FIREBASE_CONFIG) {
                logLine('✗ FIREBASE_CONFIG absent', 'ko');
                return;
            }
            logLine('✓ FIREBASE_CONFIG présent (projet: ' + window.FIREBASE_CONFIG.projectId + ')', 'ok');

            if (!window.FIREBASE_VAPID_KEY) {
                logLine('✗ Clé VAPID absente', 'ko');
            } else {
                logLine('✓ Clé VAPID présente (' + window.FIREBASE_VAPID_KEY.length + ' car.)', 'ok');
            }

            if (typeof Notification === 'undefined') {
                logLine('✗ Notifications non supportées', 'ko');
            } else {
                logLine('✓ Permission notifications : ' + Notification.permission, Notification.permission === 'granted' ? 'ok' : 'pending');
            }

            if (!('serviceWorker' in navigator)) {
                logLine('✗ Service Worker non supporté', 'ko');
                return;
            }

            navigator.serviceWorker.getRegistrations().then(function (regs) {
                var fcmReg = regs.find(function (r) {
                    var sw = r.active || r.installing || r.waiting;
                    return sw && sw.scriptURL && sw.scriptURL.indexOf('firebase-messaging-sw') !== -1;
                });
                if (fcmReg) {
                    logLine('✓ Service Worker FCM enregistré', 'ok');
                } else {
                    logLine('⚠ Service Worker FCM non trouvé — activez les notifications', 'pending');
                }

                if (typeof firebase !== 'undefined' && firebase.messaging && Notification.permission === 'granted') {
                    try {
                        var messaging = firebase.messaging();
                        var opts = { vapidKey: window.FIREBASE_VAPID_KEY };
                        if (fcmReg) opts.serviceWorkerRegistration = fcmReg;
                        messaging.getToken(opts).then(function (token) {
                            if (token) {
                                logLine('✓ Token FCM obtenu : ' + token.substring(0, 28) + '…', 'ok');
                            } else {
                                logLine('✗ Token FCM vide', 'ko');
                            }
                        }).catch(function (err) {
                            logLine('✗ getToken : ' + (err.message || err), 'ko');
                        });
                    } catch (e) {
                        logLine('✗ Erreur messaging : ' + e.message, 'ko');
                    }
                }
            });
        }

        if (btn) {
            btn.addEventListener('click', runClientTests);
        }
    })();
    </script>
</body>
</html>
