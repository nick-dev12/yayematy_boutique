<?php
/**
 * Corrige les URLs /upload/, /image/ et /js/ hardcodées dans admin/.
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

    $path = $file->getPathname();
    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }

    $original = $content;

    // onerror fallbacks
    $content = str_replace("this.src='/image/produit1.jpg'", "this.src='<?php echo public_url('/image/produit1.jpg'); ?>'", $content);
    $content = preg_replace(
        '/onerror="this\.src=\'\/image\/produit1\.jpg\'"/',
        'onerror="this.src=\'<?php echo htmlspecialchars(public_url(\'/image/produit1.jpg\'), ENT_QUOTES, \'UTF-8\'); ?>\'"',
        $content
    );

    // Patterns PHP echo for upload paths
    $replacements = [
        'src="/upload/' => 'src="<?php echo upload_public_url(\'',
        "href=\"/upload/" => "href=\"<?php echo upload_public_url('",
        "data-image-src=\"/upload/" => "data-image-src=\"<?php echo upload_public_url('",
        'data-existing-video="/upload/videos/' => 'data-existing-video="<?php echo upload_public_url(\'videos/',
        "\$video_path = '/upload/videos/" => "\$video_path = upload_public_url('videos/",
        "\$thumbnail_path = !empty(\$video['image_preview']) ? '/upload/videos/thumbnails/" => "\$thumbnail_path = !empty(\$video['image_preview']) ? upload_public_url('videos/thumbnails/",
        'src="/upload/trending/' => 'src="<?php echo upload_public_url(\'trending/',
        'src="/upload/section4/' => 'src="<?php echo upload_public_url(\'section4/',
        'src="/image/' => 'src="<?php echo public_url(\'/image/',
    ];

    // Manual fixes per file type - the generic replace above is too naive. Do targeted fixes instead.

    $fixedFiles = [];
}

// Targeted file fixes
$targets = [
    'commandes/details.php' => function ($c) {
        $c = str_replace(
            '<img src="/upload/<?php echo htmlspecialchars($img_src ?? \'\'); ?>"',
            '<img src="<?php echo upload_public_url(htmlspecialchars($img_src ?? \'\', ENT_QUOTES, \'UTF-8\')); ?>"',
            $c
        );
        $c = str_replace("onerror=\"this.src='/image/produit1.jpg'\"", 'onerror="this.src=\'<?php echo htmlspecialchars(public_url(\'/image/produit1.jpg\'), ENT_QUOTES, \'UTF-8\'); ?>\'"', $c);
        return $c;
    },
    'commandes-personnalisees/details.php' => function ($c) {
        $c = preg_replace(
            '#data-image-src="/upload/<\?php echo htmlspecialchars\(\$img_path\); \?>"#',
            'data-image-src="<?php echo upload_public_url(htmlspecialchars($img_path, ENT_QUOTES, \'UTF-8\')); ?>"',
            $c
        );
        $c = preg_replace(
            '#src="/upload/<\?php echo htmlspecialchars\(\$img_path\); \?>"#',
            'src="<?php echo upload_public_url(htmlspecialchars($img_path, ENT_QUOTES, \'UTF-8\')); ?>"',
            $c
        );
        $c = preg_replace(
            '#src="/upload/<\?php echo htmlspecialchars\(\$cp_images\[0\]\); \?>"#',
            'src="<?php echo upload_public_url(htmlspecialchars($cp_images[0], ENT_QUOTES, \'UTF-8\')); ?>"',
            $c
        );
        $c = str_replace("onerror=\"this.src='/image/produit1.jpg'\"", 'onerror="this.src=\'<?php echo htmlspecialchars(public_url(\'/image/produit1.jpg\'), ENT_QUOTES, \'UTF-8\'); ?>\'"', $c);
        return $c;
    },
    'produits/ajuster-stock.php' => function ($c) {
        $c = str_replace(
            '<img src="/upload/<?php echo htmlspecialchars($produit[\'image_principale\'] ?? \'\'); ?>"',
            '<img src="<?php echo upload_public_url(htmlspecialchars($produit[\'image_principale\'] ?? \'\', ENT_QUOTES, \'UTF-8\')); ?>"',
            $c
        );
        $c = str_replace("onerror=\"this.src='/image/produit1.jpg'\"", 'onerror="this.src=\'<?php echo htmlspecialchars(public_url(\'/image/produit1.jpg\'), ENT_QUOTES, \'UTF-8\'); ?>\'"', $c);
        return $c;
    },
    'stock/index.php' => function ($c) {
        $c = str_replace(
            "\$img = !empty(\$categorie['image']) ? '/upload/' . htmlspecialchars(\$categorie['image']) : '';",
            "\$img = !empty(\$categorie['image']) ? upload_public_url(htmlspecialchars(\$categorie['image'], ENT_QUOTES, 'UTF-8')) : '';",
            $c
        );
        $c = str_replace("onerror=\"this.src='/image/produit1.jpg'\"", 'onerror="this.src=\'<?php echo htmlspecialchars(public_url(\'/image/produit1.jpg\'), ENT_QUOTES, \'UTF-8\'); ?>\'"', $c);
        if (strpos($c, "upload_public_url") !== false && strpos($c, "site_url.php") === false && strpos($c, 'admin_auth.php') !== false) {
            // upload_public_url available via admin_auth -> site_url
        } elseif (strpos($c, 'upload_public_url') !== false && strpos($c, 'function upload_public_url') === false) {
            $c = preg_replace('/(require_once __DIR__ \. \'[^\']+admin_auth\.php\';\s*\R)/', "$1", $c);
        }
        return $c;
    },
    'parametres/section4.php' => function ($c) {
        return str_replace(
            'src="/upload/section4/<?php echo htmlspecialchars($config[\'image_fond\']); ?>"',
            'src="<?php echo upload_public_url(\'section4/\' . htmlspecialchars($config[\'image_fond\'], ENT_QUOTES, \'UTF-8\')); ?>"',
            $c
        );
    },
    'parametres/trending.php' => function ($c) {
        $c = str_replace(
            'src="/upload/trending/<?php echo htmlspecialchars($config[\'image\']); ?>"',
            'src="<?php echo upload_public_url(\'trending/\' . htmlspecialchars($config[\'image\'], ENT_QUOTES, \'UTF-8\')); ?>"',
            $c
        );
        $c = str_replace(
            'src="/image/<?php echo htmlspecialchars($config[\'image\']); ?>"',
            'src="<?php echo public_url(\'/image/\' . htmlspecialchars($config[\'image\'], ENT_QUOTES, \'UTF-8\')); ?>"',
            $c
        );
        return $c;
    },
    'parametres/videos.php' => function ($c) {
        $c = str_replace(
            "\$video_path = '/upload/videos/' . htmlspecialchars(\$video['fichier_video']);",
            "\$video_path = upload_public_url('videos/' . htmlspecialchars(\$video['fichier_video'], ENT_QUOTES, 'UTF-8'));",
            $c
        );
        $c = str_replace(
            "\$thumbnail_path = !empty(\$video['image_preview']) ? '/upload/videos/thumbnails/' . htmlspecialchars(\$video['image_preview']) : null;",
            "\$thumbnail_path = !empty(\$video['image_preview']) ? upload_public_url('videos/thumbnails/' . htmlspecialchars(\$video['image_preview'], ENT_QUOTES, 'UTF-8')) : null;",
            $c
        );
        $c = str_replace(
            'data-existing-video="/upload/videos/<?php echo htmlspecialchars($video_to_edit[\'fichier_video\']); ?>"',
            'data-existing-video="<?php echo upload_public_url(\'videos/\' . htmlspecialchars($video_to_edit[\'fichier_video\'], ENT_QUOTES, \'UTF-8\')); ?>"',
            $c
        );
        return $c;
    },
    'comptes/absences.php' => function ($c) {
        return str_replace(
            'href="/upload/<?php echo htmlspecialchars($a[\'justif_fichier\']); ?>"',
            'href="<?php echo upload_public_url(htmlspecialchars($a[\'justif_fichier\'], ENT_QUOTES, \'UTF-8\')); ?>"',
            $c
        );
    },
    'livreurs/suivi.php' => function ($c) {
        return str_replace(
            '<script src="/js/admin-livreur-suivi.js?v=<?php echo (int) @filemtime(__DIR__ . \'/../../js/admin-livreur-suivi.js\'); ?>"></script>',
            '<script src="<?php echo asset_url(\'/js/admin-livreur-suivi.js\'); ?>"></script>',
            $c
        );
    },
    'produits/modifier.php' => function ($c) {
        return str_replace("onerror=\"this.src='/image/produit1.jpg'\"", 'onerror="this.src=\'<?php echo htmlspecialchars(public_url(\'/image/produit1.jpg\'), ENT_QUOTES, \'UTF-8\'); ?>\'"', $c);
    },
    'slider/index.php' => function ($c) {
        return str_replace("onerror=\"this.src='/image/produit1.jpg'\"", 'onerror="this.src=\'<?php echo htmlspecialchars(public_url(\'/image/produit1.jpg\'), ENT_QUOTES, \'UTF-8\'); ?>\'"', $c);
    },
    'slider/modifier.php' => function ($c) {
        return str_replace("onerror=\"this.src='/image/produit1.jpg'\"", 'onerror="this.src=\'<?php echo htmlspecialchars(public_url(\'/image/produit1.jpg\'), ENT_QUOTES, \'UTF-8\'); ?>\'"', $c);
    },
    'test-notification.php' => function ($c) {
        return str_replace("['link' => '/admin/dashboard.php'", "['link' => public_url('/admin/dashboard.php')", $c);
    },
];

foreach ($targets as $rel => $fn) {
    $path = $adminRoot . '/' . $rel;
    if (!is_file($path)) {
        continue;
    }
    $content = file_get_contents($path);
    $new = $fn($content);
    if ($new !== $content) {
        file_put_contents($path, $new);
        echo "Fixed assets: {$rel}\n";
        $fixed++;
    }
}

echo "Asset fixes: {$fixed}\n";
