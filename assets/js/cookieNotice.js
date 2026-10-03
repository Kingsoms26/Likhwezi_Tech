// cookieNotice.js runs the buttons on components/cookieNotice.php

// remember the visitor dismissed the notice for a year so it does not show again
document.querySelectorAll('[data-cookie-choice]').forEach(function (button) {
    button.addEventListener('click', function () {
        document.cookie = 'cookieConsent=' + button.dataset.cookieChoice + '; max-age=31536000; path=/; SameSite=Lax; Secure';
        document.getElementById('cookieNotice').remove();
    });
});
