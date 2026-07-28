<?php
/**
 * Contrôleur — logo du site (admin)
 */

require_once __DIR__ . '/../models/model_site_brand.php';

/**
 * @return array{success:bool,message:string}
 */
function process_update_site_brand_logo(): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return ['success' => false, 'message' => 'Méthode non autorisée'];
    }

    $current = get_site_brand_config();
    $logo_alt = isset($_POST['logo_alt']) ? trim((string) $_POST['logo_alt']) : site_brand_default_alt();
    $logo_path = $current['logo_path'] ?? site_brand_default_logo_path();
    $reset_default = isset($_POST['reset_default']) && $_POST['reset_default'] === '1';

    if ($reset_default) {
        if (!empty($current['logo_path']) && strpos($current['logo_path'], '/upload/site-brand/') === 0) {
            delete_site_brand_uploaded_file($current['logo_path']);
        }
        $logo_path = site_brand_default_logo_path();
    } elseif (isset($_FILES['logo']) && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $upload = upload_site_brand_logo($_FILES['logo']);
        if (!$upload['success']) {
            return ['success' => false, 'message' => $upload['message']];
        }
        if (!empty($current['logo_path']) && $current['logo_path'] !== $upload['path'] && strpos($current['logo_path'], '/upload/site-brand/') === 0) {
            delete_site_brand_uploaded_file($current['logo_path']);
        }
        $logo_path = $upload['path'];
    }

    $result = update_site_brand_config([
        'logo_path' => $logo_path,
        'logo_alt' => $logo_alt,
    ]);

    if ($result['success']) {
        return ['success' => true, 'message' => 'Logo du site mis à jour avec succès'];
    }

    return ['success' => false, 'message' => $result['message'] ?: 'Erreur lors de la mise à jour'];
}

/**
 * @param array $file
 * @return array{success:bool,path?:string,message:string}
 */
function upload_site_brand_logo(array $file): array
{
    $upload_dir = __DIR__ . '/../upload/site-brand/';

    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
        return ['success' => false, 'message' => 'Impossible de créer le dossier de stockage'];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erreur lors de l\'envoi du fichier'];
    }

    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed, true)) {
        return ['success' => false, 'message' => 'Format non supporté. Utilisez JPG, PNG, WEBP, GIF ou SVG.'];
    }

    $max_bytes = 5 * 1024 * 1024;
    if (($file['size'] ?? 0) > $max_bytes) {
        return ['success' => false, 'message' => 'Le fichier dépasse 5 Mo'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext === '') {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
        ];
        $ext = $map[$mime] ?? 'png';
    }

    $filename = 'logo-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'message' => 'Échec de l\'enregistrement du fichier'];
    }

    if ($ext !== 'svg' && file_exists(__DIR__ . '/../includes/image_optimizer.php')) {
        require_once __DIR__ . '/../includes/image_optimizer.php';
        if (function_exists('optimize_uploaded_image')) {
            optimize_uploaded_image($dest);
        }
    }

    return [
        'success' => true,
        'path' => '/upload/site-brand/' . $filename,
        'message' => '',
    ];
}
