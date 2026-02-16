document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const navMenuWrapper = document.querySelector('.nav-menu-wrapper');
    const header = document.getElementById('header');

    // Toggle mobile menu
    if (hamburger && navMenuWrapper) {
        hamburger.addEventListener('click', function() {
            navMenuWrapper.classList.toggle('active');
        });
    }

    // Handle header scroll effect
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // PWA Installation Logic
    let deferredPrompt;
    const installAppBtn = document.getElementById('installAppBtn');

    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent the mini-infobar from appearing on mobile
        e.preventDefault();
        // Stash the event so it can be triggered later.
        deferredPrompt = e;
        // Update UI to notify the user they can install the PWA
        if (installAppBtn) {
            installAppBtn.style.display = 'inline-flex';
        }
    });

    if (installAppBtn) {
        installAppBtn.addEventListener('click', (e) => {
            e.preventDefault();
            // Hide the button
            installAppBtn.style.display = 'none';
            // Show the install prompt
            if (deferredPrompt) {
                deferredPrompt.prompt();
                // Wait for the user to respond to the prompt
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    } else {
                        console.log('User dismissed the install prompt');
                    }
                    deferredPrompt = null;
                });
            }
        });
    }

    window.addEventListener('appinstalled', () => {
        // Hide the install button
        if (installAppBtn) {
            installAppBtn.style.display = 'none';
        }
        deferredPrompt = null;
        console.log('PWA was installed');
    });
});
