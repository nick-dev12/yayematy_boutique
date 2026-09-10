<?php
require_once __DIR__ . '/../includes/admin_auth.php';
/**
 * Page de suppression de zone de livraison
 * Programmation procédurale uniquement
 */
$zone_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($zone_id <= 0) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../../models/model_zones_livraison.php';
$result = delete_zone_livraison($zone_id);

$_SESSION['success_message'] = $result['message'];
header('Location: index.php');
exit;
