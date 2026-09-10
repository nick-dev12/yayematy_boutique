<?php
/**
 * Bootstrap commun — pages admin protégées (session + rôle + URLs sous-dossier).
 */
if (defined('ADMIN_AUTH_LOADED')) {
    return;
}
define('ADMIN_AUTH_LOADED', true);

require_once dirname(__DIR__, 2) . '/includes/session_user.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start_persistent();
}

require_once dirname(__DIR__, 2) . '/includes/site_url.php';

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
    redirect_to('/admin/login.php');
}

require_once __DIR__ . '/require_access.php';
