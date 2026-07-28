<?php
/**
 * API — enregistrer la localisation du client connecté.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/session_user.php';
session_start_persistent();

if (empty($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'non_authentifie'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$lat = isset($data['latitude']) ? $data['latitude'] : null;
$lng = isset($data['longitude']) ? $data['longitude'] : null;
$accuracy = isset($data['accuracy']) ? $data['accuracy'] : null;

require_once __DIR__ . '/../../models/model_users.php';
require_once __DIR__ . '/../../includes/geo_geocode_suggest.php';

if (!users_coords_valid($lat, $lng)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'coords_invalides'], JSON_UNESCAPED_UNICODE);
    exit;
}

$label = null;
$reverse = geo_reverse_latlng($lat, $lng);
if ($reverse !== null) {
    $label = $reverse['label'];
}

$user_id = (int) $_SESSION['user_id'];
if (!users_update_location($user_id, $lat, $lng, $accuracy, $label)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'save_failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'latitude' => round((float) $lat, 8),
    'longitude' => round((float) $lng, 8),
    'accuracy' => $accuracy !== null && $accuracy !== '' ? round((float) $accuracy, 2) : null,
    'label' => $label,
], JSON_UNESCAPED_UNICODE);
