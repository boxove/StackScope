<?php
declare(strict_types=1);

require __DIR__ . '/../lib.php';

$probeUrls = buildProbeUrls($_SERVER, $customIpv4ProbeUrl, $customIpv6ProbeUrl);

sendJson([
    'current' => $probeUrls['current'],
    'ipv4' => $probeUrls['ipv4'],
    'ipv6' => $probeUrls['ipv6'],
    'note' => 'ipv4 host should have A records only; ipv6 host should have AAAA records only.',
]);
