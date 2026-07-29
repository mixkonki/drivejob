<?php

namespace DriveJob\RBAC\Util;

final class Security
{
    public static function headers(): void
    {
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: no-referrer-when-downgrade");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
        header("Cross-Origin-Opener-Policy: same-origin");
        header("Cross-Origin-Resource-Policy: same-origin");
        header("X-XSS-Protection: 0");

        // Ελαστικό CSP για dev (προσαρμόζεις για prod)
        $csp = "default-src 'self' data: blob:; img-src 'self' data: blob:; script-src 'self'; style-src 'self' 'unsafe-inline'; connect-src 'self'; frame-ancestors 'self';";
        header("Content-Security-Policy: " . $csp);
    }
}
