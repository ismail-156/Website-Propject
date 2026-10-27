    <?php require __DIR__ . '/site_footer.php'; ?>

    <script src="<?php echo appUrl('assets/js/validate.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const navbar = document.querySelector('.navbar');
            const toggleButton = document.querySelector('.nav-toggle');

            if (!navbar || !toggleButton) return;

            toggleButton.addEventListener('click', function () {
                const isOpen = navbar.classList.toggle('mobile-open');
                toggleButton.setAttribute('aria-expanded', String(isOpen));
                toggleButton.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
            });

            document.addEventListener('click', function (event) {
                if (window.innerWidth > 720) return;

                if (!navbar.contains(event.target) && navbar.classList.contains('mobile-open')) {
                    navbar.classList.remove('mobile-open');
                    toggleButton.setAttribute('aria-expanded', 'false');
                    toggleButton.setAttribute('aria-label', 'Open navigation');
                }
            });
        });
    </script>
</body>
</html>
