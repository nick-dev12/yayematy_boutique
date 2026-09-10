<?php
/**
 * Retourne l'URL de base du site pour les liens (emails, notifications, etc.)
 * Priorité : config/site.php > config/email.php > déduction depuis $_SERVER
 * 
 * @return string URL sans slash final (ex: https://sugar-paper.com)
 */
function get_site_base_url() {
    $site_url = '';

    if (file_exists(__DIR__ . '/../config/site.php')) {
        $config = require __DIR__ . '/../config/site.php';
        $site_url = $config['site_url'] ?? '';
    }

    if (empty($site_url) && file_exists(__DIR__ . '/../config/email.php')) {
        $config = require __DIR__ . '/../config/email.php';
        $site_url = $config['site_url'] ?? '';
    }

    if (!empty($site_url)) {
        return rtrim($site_url, '/');
    }

    return get_request_origin_base_url();
}

/**
 * Segment d'URL entre l'hôte et la racine du projet (ex. '' en prod, '/yayematy_boutique' en local XAMPP).
 */
function get_public_root_uri_path() {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $doc_root = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    $project_root = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');

    if ($doc_root !== '' && strncmp($project_root, $doc_root, strlen($doc_root)) === 0) {
        $suffix = substr($project_root, strlen($doc_root));
        $cached = $suffix === '' ? '' : ('/' . ltrim($suffix, '/'));
        return $cached;
    }

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script !== '' && preg_match('#^(.+?)/admin/#', $script, $m)) {
        $cached = $m[1];
        return $cached;
    }

    $cached = '';
    return $cached;
}

/**
 * Chemin absolu sous l'hôte (CSS, pages, images) — compatible sous-dossier local.
 *
 * @param string $path Ex. /css/style.css, /index.php
 */
function public_url($path = '') {
    $path = '/' . ltrim(str_replace('\\', '/', (string) $path), '/');
    if ($path === '/') {
        return rtrim(get_public_root_uri_path(), '/') ?: '/';
    }
    return rtrim(get_public_root_uri_path(), '/') . $path;
}

/**
 * URL publique d'un fichier sous /upload/.
 */
function upload_public_url($relative_path = '') {
    $relative_path = ltrim(str_replace('\\', '/', (string) $relative_path), '/');
    return public_url('/upload/' . $relative_path);
}

/**
 * Redirection HTTP vers une page du site (compatible sous-dossier).
 *
 * @param string $path Ex. /panier.php?added=1
 */
function redirect_to($path, $status = 302) {
    header('Location: ' . public_url($path), true, (int) $status);
    exit;
}

/**
 * Normalise une URL de retour (REQUEST_URI, chemin absolu ou relatif).
 *
 * @param string $url
 * @param string $fallback Chemin par défaut si vide
 */
function normalize_redirect_target($url, $fallback = '/index.php') {
    $url = trim(str_replace('\\', '/', (string) $url));
    if ($url === '') {
        return public_url($fallback);
    }

    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }

    $root = get_public_root_uri_path();
    if ($root !== '' && (str_starts_with($url, $root . '/') || $url === $root)) {
        return $url;
    }

    if (str_starts_with($url, '/')) {
        return public_url($url);
    }

    return public_url('/' . ltrim($url, '/'));
}

/**
 * URL de base pour la requête HTTP en cours : schéma + hôte + racine publique.
 */
function get_request_origin_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $root = get_public_root_uri_path();
    return rtrim($protocol . '://' . $host . $root, '/');
}

/**
 * Chemin fichier du logo vitrine présent sous /image/ ou à la racine.
 *
 * @return string Nom de fichier relatif (ex. logo.svg ou logo.png), ou chaîne vide
 */
function get_site_logo_relative_filename() {
    $image_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR;
    $root_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR;
    $candidates_image = ['yaye_maty_logo.jpeg', 'yayematy-logo.jpeg', 'yaye_maty_logo.png', 'yayematy-logo.png', 'logo.png', 'logo.jpg', 'logo-fpl.png', 'logo fpl_stock.png'];
    foreach ($candidates_image as $name) {
        if (is_file($image_dir . $name)) {
            return $name;
        }
    }
    if (is_file($root_dir . 'logo.svg')) {
        return '../logo.svg';
    }
    if (is_file($image_dir . 'logo.svg')) {
        return 'logo.svg';
    }
    return '';
}

/**
 * Segment d'URI du logo sous la racine web (ex. /image/logo.png).
 */
function get_site_logo_uri_path_relative_to_webroot() {
    $name = get_site_logo_relative_filename();
    if ($name === '') {
        return '';
    }
    if (strpos($name, '../') === 0) {
        return '/' . ltrim(substr($name, 3), '/');
    }
    $encoded = implode('/', array_map('rawurlencode', explode('/', str_replace('\\', '/', $name))));
    return '/image/' . $encoded;
}

/**
 * URL absolue du logo pour les balises img (admin local / sous-dossier WAMP OK).
 */
function get_site_logo_url_for_current_request() {
    $name = get_site_logo_relative_filename();
    if ($name === '') {
        return '';
    }
    if (strpos($name, '../') === 0) {
        $encoded = implode('/', array_map('rawurlencode', explode('/', str_replace('\\', '/', ltrim($name, './')))));
        return rtrim(get_request_origin_base_url(), '/') . '/' . $encoded;
    }
    $encoded = implode('/', array_map('rawurlencode', explode('/', str_replace('\\', '/', $name))));
    return rtrim(get_request_origin_base_url(), '/') . '/image/' . $encoded;
}
