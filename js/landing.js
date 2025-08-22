document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    const navButtons = document.querySelector('.nav-buttons');

    hamburger.addEventListener('click', function() {
        navMenu.classList.toggle('active');
        navButtons.classList.toggle('active');
    });
});
