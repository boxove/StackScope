<?php
declare(strict_types=1);

require __DIR__ . '/../../lib.php';

$result = buildResult($_SERVER, $trustedHeaders);
sendText($result['is_ipv4'] ? (string) $result['ip'] . "\n" : "\n");
