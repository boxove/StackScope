<?php
declare(strict_types=1);

return [
    // Headers are checked in order. Put the header from your trusted CDN/proxy first.
    'trusted_headers' => [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_TRUE_CLIENT_IP',
        'HTTP_FASTLY_CLIENT_IP',
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR',
    ],

    // Optional: set full URLs if your probe hosts are not ipv4./ipv6. + current host.
    // Leave empty to generate https://ipv4.example.com/api/check.php and https://ipv6.example.com/api/check.php.
    'custom_ipv4_probe_url' => '',
    'custom_ipv6_probe_url' => '',
];
