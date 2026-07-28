<?php
/**
 * Modèle — configuration du logo du site
 */

require_once __DIR__ . '/../conn/conn.php';
require_once __DIR__ . '/../includes/db_helpers.php';

if (!function_exists('site_brand_default_logo_path')) {
    function site_brand_default_logo_path(): string
    {
        return '/image/yaye_maty_logo.png';
    }
}

if (!function_exists('site_brand_default_alt')) {
    function site_brand_default_alt(): string
    {
        return 'YAYEMATY MARKET — Votre marché local';
    }
}

/**
 * @return array{id:int,logo_path:?string,logo_alt:?string,date_modification:?string}
 */
function get_site_brand_config(): array
{
    global $db;

    $defaults = [
        'id' => 1,
        'logo_path' => site_brand_default_logo_path(),
        'logo_alt' => site_brand_default_alt(),
        'date_modification' => null,
    ];

    if (!db_is_available()) {
        return $defaults;
    }

    try {
        $stmt = $db->prepare('SELECT * FROM site_brand_config WHERE id = 1 LIMIT 1');
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return $defaults;
        }
        if (empty($row['logo_path'])) {
            $row['logo_path'] = site_brand_default_logo_path();
        }
        if (empty($row['logo_alt'])) {
            $row['logo_alt'] = site_brand_default_alt();
        }
        return $row;
    } catch (PDOException $e) {
        return $defaults;
    }
}

/**
 * Chemin web du logo actif (avec repli fichier par défaut).
 */
function get_site_brand_logo_path(): string
{
    $config = get_site_brand_config();
    $path = trim((string) ($config['logo_path'] ?? ''));

    if ($path !== '' && site_brand_logo_file_exists($path)) {
        return $path;
    }

    $default = site_brand_default_logo_path();
    if (site_brand_logo_file_exists($default)) {
        return $default;
    }

    if (function_exists('get_site_logo_uri_path_relative_to_webroot')) {
        require_once __DIR__ . '/../includes/site_url.php';
        $detected = get_site_logo_uri_path_relative_to_webroot();
        if ($detected !== '') {
            return $detected;
        }
    }

    return $default;
}

function site_brand_logo_file_exists(string $web_path): bool
{
    $web_path = '/' . ltrim(str_replace('\\', '/', $web_path), '/');
    $root = dirname(__DIR__);
    $full = $root . str_replace('/', DIRECTORY_SEPARATOR, $web_path);
    return is_file($full);
}

/**
 * @param array{logo_path?:string,logo_alt?:string} $data
 * @return array{success:bool,message:string}
 */
function update_site_brand_config(array $data): array
{
    global $db;

    if (!db_is_available()) {
        return ['success' => false, 'message' => 'Connexion à la base de données indisponible'];
    }

    try {
        $logo_path = isset($data['logo_path']) ? trim((string) $data['logo_path']) : null;
        $logo_alt = isset($data['logo_alt']) ? trim((string) $data['logo_alt']) : site_brand_default_alt();

        if ($logo_alt === '') {
            $logo_alt = site_brand_default_alt();
        }

        $stmt = $db->prepare('
            INSERT INTO site_brand_config (id, logo_path, logo_alt, date_modification)
            VALUES (1, :logo_path, :logo_alt, NOW())
            ON DUPLICATE KEY UPDATE
                logo_path = VALUES(logo_path),
                logo_alt = VALUES(logo_alt),
                date_modification = NOW()
        ');
        $ok = $stmt->execute([
            'logo_path' => $logo_path,
            'logo_alt' => $logo_alt,
        ]);

        return $ok
            ? ['success' => true, 'message' => '']
            : ['success' => false, 'message' => 'Échec de l\'enregistrement'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function delete_site_brand_uploaded_file(string $web_path): bool
{
    $web_path = '/' . ltrim(str_replace('\\', '/', $web_path), '/');
    if (strpos($web_path, '/upload/site-brand/') !== 0) {
        return false;
    }
    $full = dirname(__DIR__) . str_replace('/', DIRECTORY_SEPARATOR, $web_path);
    if (is_file($full)) {
        return unlink($full);
    }
    return false;
}
