// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle && sidebar) {
        // Toggle sidebar when menu button is clicked
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 992 && 
                sidebar.classList.contains('active') && 
                !sidebar.contains(e.target) && 
                e.target !== menuToggle) {
                sidebar.classList.remove('active');
            }
        });

        // Close sidebar when a menu item is clicked (on mobile)
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('active');
                }
            });
        });
    }
});