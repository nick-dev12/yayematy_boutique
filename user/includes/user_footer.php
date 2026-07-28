    </main>
</div>

<script>
    (function() {
        function closeUserSidebar() {
            var sidebar = document.getElementById('userSidebar');
            var overlay = document.getElementById('sidebarOverlay');
            if (sidebar && overlay) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                closeUserSidebar();
            }
        });
    })();
</script>
<?php include __DIR__ . '/bottom_nav.php'; ?>
<?php include __DIR__ . '/../../includes/social_floating.php'; ?>
<?php
require_once __DIR__ . '/../../includes/product_share_assets.php';
if (product_share_should_load_assets()) {
    product_share_render_assets();
}
?>
<?php include __DIR__ . '/../../includes/firebase_notifications_scripts.php'; ?>
</body>
</html>

