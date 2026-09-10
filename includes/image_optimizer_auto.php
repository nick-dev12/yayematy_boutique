<?php
/**
 * Compression automatique des images — sans commande CLI.
 * - À l'affichage : upload_image_url() compresse l'image si besoin.
 * - En arrière-plan : quelques fichiers non optimisés traités à chaque requête HTTP.
 */

require_once __DIR__ . '/image_optimizer.php';

if (!defined('IMAGE_OPTIMIZER_AUTO_BATCH_SIZE')) {
    define('IMAGE_OPTIMIZER_AUTO_BATCH_SIZE', 3);
}

if (!defined('IMAGE_OPTIMIZER_AUTO_MIN_INTERVAL_SEC')) {
    define('IMAGE_OPTIMIZER_AUTO_MIN_INTERVAL_SEC', 2);
}

/**
 * Indique si une image raster sous upload/ est déjà optimisée (variantes présentes).
 */
function image_optimizer_is_already_optimized($relative_path)
{
    $relative_path = trim(str_replace('\\', '/', (string) $relative_path), '/');
    if ($relative_path === '') {
        return true;
    }

    $ext = strtolower(pathinfo($relative_path, PATHINFO_EXTENSION));
    if ($ext === 'webp') {
        return true;
    }

    $upload_root = dirname(__DIR__) . '/upload/';
    $stem = pathinfo($relative_path, PATHINFO_FILENAME);
    if ($stem === '') {
        return true;
    }

    if (str_ends_with($stem, '_md') || str_ends_with($stem, '_sm')) {
        return true;
    }

    $dir = dirname($relative_path);
    $dir_prefix = ($dir === '.' || $dir === '') ? '' : $dir . '/';

    foreach (['webp', 'jpg', 'jpeg'] as $optimized_ext) {
        $main = $dir_prefix . $stem . '.' . $optimized_ext;
        if (!is_file($upload_root . $main)) {
            continue;
        }
        $md = $dir_prefix . $stem . '_md.' . $optimized_ext;
        $sm = $dir_prefix . $stem . '_sm.' . $optimized_ext;
        if (is_file($upload_root . $md) || is_file($upload_root . $sm)) {
            return true;
        }
    }

    $webp_main = $dir_prefix . $stem . '.webp';
    if (is_file($upload_root . $webp_main)) {
        return true;
    }

    return false;
}

/**
 * Compresse une image relative (produits/x.jpg) si nécessaire et met à jour la BDD.
 */
function image_optimizer_maybe_compress_relative($relative_path)
{
    static $processed = [];

    $relative_path = trim(str_replace('\\', '/', (string) $relative_path), '/');
    if ($relative_path === '' || isset($processed[$relative_path])) {
        return;
    }
    $processed[$relative_path] = true;

    if (!image_optimizer_gd_available()) {
        return;
    }

    if (image_optimizer_is_already_optimized($relative_path)) {
        return;
    }

    $upload_root = dirname(__DIR__) . '/upload/';
    $abs = $upload_root . $relative_path;
    if (!is_file($abs)) {
        return;
    }

    $rel_subdir = dirname($relative_path);
    $rel_subdir = ($rel_subdir === '.' ? '' : $rel_subdir);

    $result = image_optimizer_compress_existing_file($abs, $rel_subdir, 'auto_');
    if (empty($result['success'])) {
        return;
    }

    $new_rel = trim(str_replace('\\', '/', (string) ($result['relative_path'] ?? '')), '/');
    if ($new_rel === '' || $new_rel === $relative_path) {
        return;
    }

    image_optimizer_auto_update_database_paths($relative_path, $new_rel);
}

/**
 * Met à jour les chemins en base après conversion automatique.
 */
function image_optimizer_auto_update_database_paths($old_rel, $new_rel)
{
    global $db;

    if (!isset($db) || !($db instanceof PDO)) {
        return;
    }

    if (!function_exists('image_db_apply_path_mapping')) {
        require_once __DIR__ . '/image_optimizer_db.php';
    }

    if (function_exists('image_db_apply_path_mapping')) {
        image_db_apply_path_mapping($db, $old_rel, $new_rel);
    }
}

/**
 * Cherche des fichiers raster non optimisés sous upload/.
 *
 * @return list<string>
 */
function image_optimizer_find_uncompressed_files($limit = 3)
{
    $upload_root = dirname(__DIR__) . '/upload';
    if (!is_dir($upload_root)) {
        return [];
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $found = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($upload_root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file_info) {
        if (!$file_info->isFile()) {
            continue;
        }

        $ext = strtolower($file_info->getExtension());
        if (!in_array($ext, $allowed, true)) {
            continue;
        }

        $abs = $file_info->getPathname();
        $rel = ltrim(str_replace('\\', '/', substr($abs, strlen($upload_root))), '/');
        if ($rel === '' || image_optimizer_is_already_optimized($rel)) {
            continue;
        }

        $found[] = $rel;
        if (count($found) >= $limit) {
            break;
        }
    }

    return $found;
}

/**
 * Traite un petit lot d'images à chaque requête HTTP (file d'attente progressive).
 */
function image_optimizer_auto_tick()
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    if (!image_optimizer_gd_available()) {
        return;
    }

    $storage_dir = dirname(__DIR__) . '/storage';
    if (!is_dir($storage_dir)) {
        @mkdir($storage_dir, 0755, true);
    }

    $lock_file = $storage_dir . '/image_optimizer_auto.lock';
    $fp = @fopen($lock_file, 'c+');
    if ($fp === false) {
        return;
    }

    if (!flock($fp, LOCK_EX | LOCK_NB)) {
        fclose($fp);
        return;
    }

    $state_file = $storage_dir . '/image_optimizer_auto.state';
    $now = time();
    $last_run = is_file($state_file) ? (int) @file_get_contents($state_file) : 0;
    if ($last_run > 0 && ($now - $last_run) < IMAGE_OPTIMIZER_AUTO_MIN_INTERVAL_SEC) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return;
    }

    $batch = image_optimizer_find_uncompressed_files(IMAGE_OPTIMIZER_AUTO_BATCH_SIZE);
    foreach ($batch as $rel) {
        image_optimizer_maybe_compress_relative($rel);
    }

    @file_put_contents($state_file, (string) $now);

    flock($fp, LOCK_UN);
    fclose($fp);
}
