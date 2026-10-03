// staffLogin.js runs the show password button on staffLogin.php

// show or hide the password
document.querySelector('.password-toggle').addEventListener('click', function () {
    const input = document.getElementById('password');
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    this.setAttribute('aria-pressed', String(!showing));
    this.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    this.querySelector('i').className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
});
