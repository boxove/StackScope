<?php
declare(strict_types=1);

require __DIR__ . '/../lib.php';

$family = strtolower((string) ($_GET['family'] ?? 'any'));
$wanted = match ($family) {
    'ipv4', '4' => 4,
    'ipv6', '6' => 6,
    default => null,
};
$result = buildResult($_SERVER, $trustedHeaders);

sendText($wanted === null || $result['version'] === $wanted ? (string) ($result['ip'] ?? '') . "\n" : "\n");
