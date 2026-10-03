<?php
// security.php sends the security headers and starts the session
// required at the very top of every page before any output

// hide the php version and server details
header_remove('X-Powered-By');
header_remove('Server');

// security headers
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

// content security policy, only the sites the pages actually load from
// scripts only run from files, unsafe-inline is still needed for styles until the style attributes are moved out
$csp = [
    "default-src 'self'",
    "script-src 'self' https://cdn.jsdelivr.net",
    "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
    "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com",
    "img-src 'self' data: blob: https://res.cloudinary.com",
    "connect-src 'self'",
    "frame-src https://www.google.com",
    "object-src 'none'",
    "frame-ancestors 'self'",
    "base-uri 'self'",
    "form-action 'self' https://www.payfast.co.za https://sandbox.payfast.co.za",
];
header('Content-Security-Policy: ' . implode('; ', $csp));

// session cookie only sent over https and hidden from javascript
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
