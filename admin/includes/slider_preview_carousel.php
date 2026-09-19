<?php
/**
 * Aperçu carrousel des slides (admin).
 *
 * @var array<int, array<string, mixed>> $slider_preview_slides
 * @var string $slider_preview_class Classes additionnelles sur le conteneur
 */
$slider_preview_slides = is_array($slider_preview_slides ?? null) ? $slider_preview_slides : [];
$slider_preview_class = trim((string) ($slider_preview_class ?? ''));
if ($slider_preview_slides === []) {
    return;
}

require_once __DIR__ . '/../../includes/image_optimizer.php';
require_once __DIR__ . '/../../includes/site_url.php';
?>
<div class="admin-slider-preview <?php echo htmlspecialchars($slider_preview_class); ?>" aria-label="Aperçu du slider">
    <div class="admin-slider-preview__track owl-carousel">
        <?php foreach ($slider_preview_slides as $slide): ?>
        <div class="admin-slider-preview__slide">
            <img src="<?php echo htmlspecialchars(upload_image_url('slider/' . ($slide['image'] ?? ''), 'md')); ?>"
                alt="<?php echo htmlspecialchars($slide['titre'] ?? 'Slide'); ?>"
                loading="lazy"
                decoding="async"
                onerror="this.src='<?php echo htmlspecialchars(public_url('/image/produit1.jpg'), ENT_QUOTES, 'UTF-8'); ?>'">
            <?php if (!empty($slide['titre']) || !empty($slide['paragraphe'])): ?>
            <div class="admin-slider-preview__caption">
                <?php if (!empty($slide['titre'])): ?>
                <strong><?php echo htmlspecialchars($slide['titre']); ?></strong>
                <?php endif; ?>
                <?php if (!empty($slide['paragraphe'])): ?>
                <span><?php echo htmlspecialchars($slide['paragraphe']); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
