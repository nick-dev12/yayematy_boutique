<?php
require __DIR__ . '/../services/firebase_push.php';
$p = require __DIR__ . '/../config/firebase_server.php';
$cfg = require __DIR__ . '/../config/firebase_config.php';

echo 'projectId: ' . ($cfg['projectId'] ?? '?') . PHP_EOL;
echo 'credentials: ' . (file_exists($p['credentials_path']) ? 'OK' : 'MISSING') . PHP_EOL;

$token = firebase_get_access_token($p['credentials_path']);
echo 'oauth_token: ' . ($token ? 'OK (len=' . strlen($token) . ')' : 'FAIL') . PHP_EOL;

$sw = file_get_contents(__DIR__ . '/../firebase-messaging-sw.js');
echo 'sw_project: ' . (strpos($sw, 'yaye-bc53c') !== false ? 'OK' : 'FAIL') . PHP_EOL;
echo 'vapid_len: ' . strlen(trim($cfg['vapidKey'] ?? '')) . PHP_EOL;
