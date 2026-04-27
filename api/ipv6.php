<?php
declare(strict_types=1);

require __DIR__ . '/../lib.php';

$result = buildResult($_SERVER, $trustedHeaders);
sendText($result['is_ipv6'] ? (string) $result['ip'] . "\n" : "\n");
