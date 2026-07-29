</div>
<!-- End Page Content -->
</main>
<!-- End Admin Main Content -->

<!-- Admin Scripts -->
<script>
    // Toggle mobile navigation
    function toggleAdminNav() {
        document.querySelector('.admin-nav').classList.toggle('active');
        document.querySelector('.admin-main').classList.toggle('nav-active');
    }

    // Toggle notifications (placeholder)
    function toggleNotifications() {
        alert('Το σύστημα ειδοποιήσεων θα υλοποιηθεί σύντομα.');
    }

    // Close mobile nav when clicking outside
    document.addEventListener('click', function(event) {
        const nav = document.querySelector('.admin-nav');
        const toggle = document.querySelector('.admin-nav-toggle');

        if (!nav.contains(event.target) && !toggle.contains(event.target)) {
            nav.classList.remove('active');
            document.querySelector('.admin-main').classList.remove('nav-active');
        }
    });

    // Add active class to current page in navigation
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.admin-nav-link');

        navLinks.forEach(link => {
            if (currentPath.includes(link.getAttribute('href'))) {
                link.classList.add('active');
            }
        });
    });
</script>

<!-- Additional Scripts -->
<?php if (isset($scripts)): ?>
    <?php foreach ($scripts as $script): ?>
        <script src="<?php echo $script; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>

</html>