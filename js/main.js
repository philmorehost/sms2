document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggler = document.getElementById('sidebarToggler');
    const wrapper = document.querySelector('.wrapper');

    // Logic for desktop sidebar collapse
    if (sidebarToggler && wrapper) {
        sidebarToggler.addEventListener('click', function() {
            wrapper.classList.toggle('sidebar-collapsed');
            // Optional: Save state in localStorage to remember user's preference
            if (wrapper.classList.contains('sidebar-collapsed')) {
                localStorage.setItem('sidebarState', 'collapsed');
            } else {
                localStorage.setItem('sidebarState', 'expanded');
            }
        });
    }

    // Check for saved sidebar state on page load
    if (wrapper && localStorage.getItem('sidebarState') === 'collapsed') {
        wrapper.classList.add('sidebar-collapsed');
    }
});
