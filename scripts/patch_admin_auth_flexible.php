<?php
/**
 * Remplace le bloc session manuel par admin_auth.php (pages admin protégées).
 */
$adminRoot = realpath(__DIR__ . '/../admin');

$skipBasenames = [
    'login.php', 'logout.php', 'index.php', 'inscription-admin.php',
    'mot-de-passe-oublie.php', 'reinitialiser-mot-de-passe.php',
    'admin_auth.php', 'require_access.php', 'nav.php', 'footer.php',
    'bottom_nav.php', 'btn_retour_site.php', 'gtranslate_auth_bar.php',
    'render_dash_product_card.php', 'dashboard_data.php',
];

$skipIfContains = ['ajax_', 'Content-Type: application/json'];

$authPaths = [
    "require_once __DIR__ . '/includes/admin_auth.php';",
    "require_once __DIR__ . '/../includes/admin_auth.php';",
    "require_once __DIR__ . '/../../includes/admin_auth.php';",
    "require_once __DIR__ . '/../../../includes/admin_auth.php';",
];

$sessionPaths = [
    "require_once __DIR__ . '/includes/session_user.php';",
    "require_once __DIR__ . '/../includes/session_user.php';",
    "require_once __DIR__ . '/../../includes/session_user.php';",
    "require_once __DIR__ . '/../../../includes/session_user.php';",
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($adminRoot, FilesystemIterator::SKIP_DOTS)
);

$patched = 0;
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $basename = $file->getFilename();
    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($adminRoot) + 1));

    if (in_array($basename, $skipBasenames, true) || strpos($relative, 'includes/') === 0) {
        continue;
    }

    $content = file_get_contents($file->getPathname());
    if ($content === false || strpos($content, 'admin_auth.php') !== false) {
        continue;
    }

    foreach ($skipIfContains as $needle) {
        if (stripos($basename, $needle) !== false || stripos($content, $needle) !== false) {
            continue 2;
        }
    }

    $newContent = $content;
    $replaced = false;

    foreach ($sessionPaths as $i => $sessionPath) {
        if (strpos($newContent, $sessionPath) === false) {
            continue;
        }

        $authPath = $authPaths[$i] ?? null;
        if ($authPath === null) {
            continue;
        }

        $newContent = str_replace($sessionPath, $authPath, $newContent, $count);
        if ($count === 0) {
            continue;
        }

        $newContent = preg_replace(
            '/\n\s*session_start_persistent\(\);\s*\n\s*(?:\/\/[^\n]*\n\s*)?if\s*\(!isset\(\$_SESSION\[\'admin_id\'\]\)[^}]+\}\s*\n/s',
            "\n",
            $newContent,
            1,
            $authRemoved
        );

        if ($authRemoved > 0) {
            $replaced = true;
            break;
        }

        // Fallback : retirer seulement session_start si pas de bloc auth standard
        $newContent = preg_replace('/\n\s*session_start_persistent\(\);\s*\n/', "\n", $newContent, 1, $sessionRemoved);
        if ($sessionRemoved > 0) {
            $replaced = true;
            break;
        }
    }

    if (!$replaced || $newContent === $content) {
        continue;
    }

    $newContent = str_replace(
        [
            "require_once __DIR__ . '/../../includes/admin_route_access.php';\nadmin_route_enforce();\n\n",
            "require_once __DIR__ . '/../includes/admin_route_access.php';\nadmin_route_enforce();\n\n",
            "require_once __DIR__ . '/includes/admin_route_access.php';\nadmin_route_enforce();\n\n",
        ],
        '',
        $newContent
    );

    file_put_contents($file->getPathname(), $newContent);
    echo "Patched: {$relative}\n";
    $patched++;
}

echo "Patched total: {$patched}\n";
