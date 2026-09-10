<?php
/**
 * Corrige les chemins require admin_auth.php selon la profondeur sous admin/.
 */
$adminRoot = realpath(__DIR__ . '/../admin');
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($adminRoot, FilesystemIterator::SKIP_DOTS)
);

$fixed = 0;
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($adminRoot) + 1));
    if (strpos($relative, 'includes/') === 0) {
        continue;
    }

    $content = file_get_contents($file->getPathname());
    if ($content === false || strpos($content, 'admin_auth.php') === false) {
        continue;
    }

    $depth = substr_count($relative, '/');
    if ($depth === 0) {
        $correct = "require_once __DIR__ . '/includes/admin_auth.php';";
    } else {
        $prefix = str_repeat('../', $depth);
        $correct = "require_once __DIR__ . '/{$prefix}includes/admin_auth.php';";
    }

    $newContent = preg_replace(
        "/require_once __DIR__ \. '(\/\.\.)+\/includes\/admin_auth\.php';/",
        $correct,
        $content,
        1,
        $count
    );

    if ($count > 0 && $newContent !== $content) {
        file_put_contents($file->getPathname(), $newContent);
        echo "Fixed: {$relative}\n";
        $fixed++;
    }
}

echo "Fixed total: {$fixed}\n";
