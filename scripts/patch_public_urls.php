<?php
/**
 * Corrige les chemins absolus /css, /js, /image, /pages pour un déploiement en sous-dossier.
 * Usage : php scripts/patch_public_urls.php
 */
$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$skip_dirs = ['/vendor/', '/.git/', '/appyayeboutique/', '/tracking-server/'];
$changed = 0;

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = str_replace('\\', '/', $file->getPathname());
    foreach ($skip_dirs as $skip) {
        if (strpos($path, $skip) !== false) {
            continue 2;
        }
    }
    if (basename($path) === 'patch_public_urls.php') {
        continue;
    }

    $content = file_get_contents($path);
    $original = $content;

    $content = preg_replace(
        '#href="/((?:css|js)/[^"]+)<\?php echo asset_version_query\(\); \?>"#',
        'href="<?php echo asset_url(\'/$1\'); ?>"',
        $content
    );

    $content = preg_replace(
        '#href="/((?:css|js)/[^"]+)<\?php echo \$asset_version \? \'\?v=\' \. \$asset_version : \'\'; \?>"#',
        'href="<?php echo asset_url(\'/$1\'); ?>"',
        $content
    );

    $content = preg_replace(
        '#src="/((?:css|js|image)/[^"]+)<\?php echo asset_version_query\(\); \?>"#',
        'src="<?php echo asset_url(\'/$1\'); ?>"',
        $content
    );

    $content = preg_replace(
        '#href="/manifest\.json"#',
        'href="<?php echo public_url(\'/manifest.json\'); ?>"',
        $content
    );

    $content = preg_replace(
        '#action="/([^"]+\.php[^"]*)"#',
        'action="<?php echo public_url(\'/$1\'); ?>"',
        $content
    );

    $content = preg_replace_callback(
        '#href="/((?:[a-zA-Z0-9_\-/]+)\.php(?:\?[^"]*)?)"#',
        static function (array $m) {
            $target = $m[1];
            if (strpos($target, '<?php') !== false) {
                return $m[0];
            }
            return 'href="<?php echo public_url(\'/' . $target . '\'); ?>"';
        },
        $content
    );

    if ($content !== $original) {
        file_put_contents($path, $content);
        $changed++;
        echo basename(dirname($path)) . '/' . basename($path) . "\n";
    }
}

echo "\nFichiers modifiés : $changed\n";
