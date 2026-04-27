<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

$trustedHeaders = $config['trusted_headers'];
$customIpv4ProbeUrl = $config['custom_ipv4_probe_url'];
$customIpv6ProbeUrl = $config['custom_ipv6_probe_url'];

function normalizeIp(string $value): ?string
{
    $value = trim($value);

    if ($value === '') {
        return null;
    }

    foreach (explode(',', $value) as $part) {
        $ip = trim($part);

        if (preg_match('/^\[(.+)](?::\d+)?$/', $ip, $matches) === 1) {
            $ip = $matches[1];
        }

        if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
            return $ip;
        }
    }

    return null;
}

function detectClientIp(array $server, array $headers): array
{
    foreach ($headers as $header) {
        if (!isset($server[$header])) {
            continue;
        }

        $ip = normalizeIp((string) $server[$header]);

        if ($ip !== null) {
            return ['ip' => $ip, 'source' => $header];
        }
    }

    return ['ip' => null, 'source' => null];
}

function ipVersion(?string $ip): ?int
{
    if ($ip === null) {
        return null;
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
        return 6;
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
        return 4;
    }

    return null;
}

function currentUrlParts(array $server): array
{
    $scheme = (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($server['HTTP_HOST'] ?? '');
    $hostWithoutPort = preg_replace('/:\d+$/', '', $host) ?? $host;
    $port = $hostWithoutPort !== $host ? substr($host, strlen($hostWithoutPort)) : '';
    $hostParts = explode('.', $hostWithoutPort);
    $baseHost = in_array($hostParts[0] ?? '', ['ipv4', 'ipv6'], true)
        ? implode('.', array_slice($hostParts, 1))
        : $hostWithoutPort;
    $scriptPath = (string) ($server['SCRIPT_NAME'] ?? '/index.php');
    $basePath = preg_replace('#/api/[^/]+(?:\.php|/index\.php)$#', '', $scriptPath) ?? '';

    return [$scheme, $hostWithoutPort, $port, $baseHost, $basePath];
}

function buildProbeUrls(array $server, string $customIpv4ProbeUrl, string $customIpv6ProbeUrl): array
{
    [$scheme, , $port, $baseHost, $basePath] = currentUrlParts($server);
    $apiPath = rtrim($basePath, '/') . '/api/check.php';

    return [
        'current' => $apiPath,
        'ipv4' => $customIpv4ProbeUrl !== '' ? $customIpv4ProbeUrl : sprintf('%s://ipv4.%s%s%s', $scheme, $baseHost, $port, $apiPath),
        'ipv6' => $customIpv6ProbeUrl !== '' ? $customIpv6ProbeUrl : sprintf('%s://ipv6.%s%s%s', $scheme, $baseHost, $port, $apiPath),
    ];
}

function buildResult(array $server, array $headers): array
{
    $detected = detectClientIp($server, $headers);
    $ip = $detected['ip'];
    $version = ipVersion($ip);
    $ipv4 = $version === 4 ? $ip : null;
    $ipv6 = $version === 6 ? $ip : null;

    return [
        'ok' => $version !== null,
        'ip' => $ip,
        'ipv4' => $ipv4,
        'ipv6' => $ipv6,
        'version' => $version,
        'is_ipv4' => $version === 4,
        'is_ipv6' => $version === 6,
        'source' => $detected['source'],
        'host' => $server['HTTP_HOST'] ?? '',
        'user_agent' => $server['HTTP_USER_AGENT'] ?? '',
        'time' => gmdate('c'),
    ];
}

function sendCorsHeaders(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
    header('Access-Control-Max-Age: 86400');
}

function sendSecurityHeaders(): void
{
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer-when-downgrade');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

function handleCorsPreflight(array $server): void
{
    if (($server['REQUEST_METHOD'] ?? 'GET') !== 'OPTIONS') {
        return;
    }

    sendCorsHeaders();
    sendSecurityHeaders();
    header('Cache-Control: no-store, private');
    http_response_code(204);
    exit;
}

function sendJson(array $data): never
{
    sendCorsHeaders();
    sendSecurityHeaders();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function sendText(string $text): never
{
    sendCorsHeaders();
    sendSecurityHeaders();
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo $text;
    exit;
}

handleCorsPreflight($_SERVER);
