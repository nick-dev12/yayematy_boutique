<?php
/**
 * Applique admin_auth.php aux pages admin protégées.
 */
$adminRoot = realpath(__DIR__ . '/../admin');

$skipBasenames = [
    'login.php', 'logout.php', 'index.php', 'inscription-admin.php',
    'mot-de-passe-oublie.php', 'reinitialiser-mot-de-passe.php',
    'admin_auth.php', 'require_access.php', 'nav.php', 'footer.php',
    'bottom_nav.php', 'btn_retour_site.php', 'gtranslate_auth_bar.php',
    'render_dash_product_card.php', 'dashboard_data.php',
];

$blocks = [
    [
        'auth' => "require_once __DIR__ . '/includes/admin_auth.php';",
        'search' => <<<'PHP'
require_once __DIR__ . '/../includes/session_user.php';
session_start_persistent();

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
    header('Location: login.php');
    exit;
}

PHP,
    ],
    [
        'auth' => "require_once __DIR__ . '/../includes/admin_auth.php';",
        'search' => <<<'PHP'
require_once __DIR__ . '/../../includes/session_user.php';
session_start_persistent();

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
    header('Location: ../login.php');
    exit;
}

PHP,
    ],
    [
        'auth' => "require_once __DIR__ . '/../../includes/admin_auth.php';",
        'search' => <<<'PHP'
require_once __DIR__ . '/../../../includes/session_user.php';
session_start_persistent();

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
    header('Location: ../../login.php');
    exit;
}

PHP,
    ],
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

    $newContent = $content;
    foreach ($blocks as $block) {
        if (strpos($newContent, $block['search']) !== false) {
            $newContent = str_replace($block['search'], $block['auth'] . "\n", $newContent, $count);
            if ($count > 0) {
                break;
            }
        }
    }

    if ($newContent === $content) {
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
