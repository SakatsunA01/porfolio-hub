<?php

return [
    'x_frame_options' => env('SECURITY_X_FRAME_OPTIONS', 'SAMEORIGIN'),
    'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
    'permissions_policy' => env('SECURITY_PERMISSIONS_POLICY', 'geolocation=(), microphone=(), camera=()'),
    'hsts' => env('SECURITY_HSTS', 'max-age=31536000; includeSubDomains; preload'),
    'csp' => env(
        'SECURITY_CSP_REPORT_ONLY',
        "default-src 'self'; connect-src 'self' https:; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; script-src 'self' 'unsafe-inline'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
    ),
];
