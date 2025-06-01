document.addEventListener('DOMContentLoaded', function() {
    // Theme toggle functionality
    const themeToggle = document.getElementById('themeToggle');
    const desktopThemeToggle = document.getElementById('desktopThemeToggle');
    const html = document.documentElement;

    function setTheme(theme) {
        html.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);
        
        // Update Chart.js colors if needed
        updateChartColors(theme);
    }

    function toggleTheme() {
        const currentTheme = html.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        setTheme(newTheme);
    }

    // Initialize theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    // Add event listeners
    if (themeToggle) themeToggle.addEventListener('click', toggleTheme);
    if (desktopThemeToggle) desktopThemeToggle.addEventListener('click', toggleTheme);

    // Toggle sidebar on mobile
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            new bootstrap.Alert(alert).close();
        });
    }, 5000);

    // Auto-hide toast notifications after 5 seconds
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        setTimeout(() => {
            const bsToast = bootstrap.Toast.getInstance(toast);
            if (bsToast) {
                bsToast.hide();
            }
        }, 5000);
    });

    // Add animation to cards on page load
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Function to update chart colors based on theme
    function updateChartColors(theme) {
        // You can implement chart color updates here if needed
        // This would require storing chart instances and updating their options
    }
});