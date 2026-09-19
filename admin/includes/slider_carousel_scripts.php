<?php
/**
 * Scripts Owl pour aperçus slider admin (après jQuery).
 */
if (!function_exists('asset_url')) {
    require_once __DIR__ . '/../../includes/asset_version.php';
}
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
<script src="<?php echo asset_url('/js/owl.carousel.js'); ?>" defer></script>
<script src="<?php echo asset_url('/js/owl.autoplay.js'); ?>" defer></script>
<script src="<?php echo asset_url('/js/owl.navigation.js'); ?>" defer></script>
<link rel="stylesheet" href="<?php echo asset_url('/css/owl.carousel.min.css'); ?>">
<script defer>
(function () {
    var attempts = 0;

    function initAdminSliderPreviews() {
        attempts += 1;
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.owlCarousel === 'undefined') {
            if (attempts < 40) {
                window.setTimeout(initAdminSliderPreviews, 50);
            }
            return;
        }
        jQuery('.admin-slider-preview__track').each(function () {
            var $track = jQuery(this);
            if ($track.hasClass('owl-loaded')) {
                return;
            }
            var count = $track.children().length;
            var multi = count > 1;
            $track.owlCarousel({
                items: 1,
                loop: multi,
                dots: multi,
                autoplay: multi,
                autoplayTimeout: 5000,
                autoplayHoverPause: true,
                smartSpeed: 600,
                nav: multi,
                navText: [
                    '<i class="fas fa-chevron-left" aria-hidden="true"></i>',
                    '<i class="fas fa-chevron-right" aria-hidden="true"></i>'
                ]
            });
        });
    }

    window.addEventListener('load', initAdminSliderPreviews);
})();
</script>
