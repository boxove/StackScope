<?php
declare(strict_types=1);

require __DIR__ . '/../lib.php';

sendJson(buildResult($_SERVER, $trustedHeaders));
