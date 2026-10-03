// dashboardForms.js handles form attributes used across the staff pages, loaded once by components/dashboard.php
// data-confirm on a form asks before it is sent
// data-submit-on-change on a field sends its form as soon as it changes

// ask before sending, cancel stops the form
document.addEventListener('submit', function (event) {
    const message = event.target.dataset.confirm;
    if (message && !confirm(message)) {
        event.preventDefault();
    }
});

// save straight away when the field changes
document.addEventListener('change', function (event) {
    if (event.target.matches('[data-submit-on-change]')) {
        event.target.form.submit();
    }
});
