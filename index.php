<?php
declare(strict_types=1);

/**
 * IPv4/IPv6 detection page for PHP sites behind a CDN/reverse proxy.
 *
 * For dual-stack testing, point ipv4.example.com to A records only and
 * ipv6.example.com to AAAA records only, both serving this same script.
 */

require __DIR__ . '/lib.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$result = buildResult($_SERVER, $trustedHeaders);
$probeUrls = buildProbeUrls($_SERVER, $customIpv4ProbeUrl, $customIpv6ProbeUrl);
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$family = strtolower((string) ($_GET['family'] ?? 'any'));

if ($path === '/api/check' || ($_GET['format'] ?? '') === 'json') {
    sendJson($result);
}

if ($path === '/api/probes') {
    sendJson([
        'current' => $probeUrls['current'],
        'ipv4' => $probeUrls['ipv4'],
        'ipv6' => $probeUrls['ipv6'],
        'note' => 'ipv4 host should have A records only; ipv6 host should have AAAA records only.',
    ]);
}

if ($path === '/api/ip' || isset($_GET['raw'])) {
    $wanted = match ($family) {
        'ipv4', '4' => 4,
        'ipv6', '6' => 6,
        default => null,
    };

    sendText($wanted === null || $result['version'] === $wanted ? (string) ($result['ip'] ?? '') . "\n" : "\n");
}

if ($path === '/api/ipv4') {
    sendText($result['is_ipv4'] ? (string) $result['ip'] . "\n" : "\n");
}

if ($path === '/api/ipv6') {
    sendText($result['is_ipv6'] ? (string) $result['ip'] . "\n" : "\n");
}

sendSecurityHeaders();
header('Cache-Control: no-store, private');

$initialIp = $result['ip'] ?? '未获取到有效 IP';
$initialVersion = $result['version'] ? 'IPv' . $result['version'] : '未知';
$probeConfig = json_encode($probeUrls, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IPv4 / IPv6 双栈检测</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <main class="page">
    <section class="hero" aria-label="IPv4 / IPv6 双栈检测首页">
      <div id="canvas" class="canvas">
        <aside class="summary reveal-pop" style="--delay:80ms;--dur:680ms" aria-label="当前连接">
          <p class="summary-label">Current Connection</p>
          <p class="summary-ip"><?php echo e($initialIp); ?></p>
          <div class="summary-meta">
            <span class="pill"><?php echo e($initialVersion); ?></span>
            <span class="pill">来源：<?php echo e($result['source'] ?? '无'); ?></span>
            <span class="pill">UTC <?php echo e($result['time']); ?></span>
          </div>
        </aside>

        <div id="tagUIUX" class="tag tag-uiux reveal-left" style="--rot:-8deg;--delay:240ms;--dur:720ms">
          <span>IPv4/IPv6</span>
          <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
        </div>

        <h1 id="title" class="title reveal" style="--delay:120ms;--dur:820ms">Dual<br>Stack</h1>
        <div id="tagYear" class="tag tag-year reveal-pop" style="--rot:6deg;--delay:420ms;--dur:680ms"><?php echo e($initialVersion); ?></div>
        <div id="arrow" class="arrow reveal-right" style="--rot:22deg;--delay:520ms;--dur:620ms" aria-hidden="true"></div>
        <a id="status" class="status-link reveal-right" style="--delay:620ms;--dur:680ms" href="/api/ip" target="_blank" rel="noreferrer"><?php echo e($initialIp); ?></a>
      </div>
    </section>

    <section class="panel" aria-label="双栈检测结果与接口">
      <article class="card">
        <p class="summary-label">Live Probe</p>
        <h2>连通性检测</h2>
        <p>页面会请求当前站点的 IPv4 与 IPv6 探测接口，验证浏览器在不同网络栈下拿到的公网地址。</p>
        <div class="probe-row">
          <div class="probe"><span>IPv4</span><span id="ipv4-status" class="loading">检测中</span></div>
          <div class="probe"><span>IPv4 地址</span><span id="ipv4-ip">等待响应...</span></div>
          <div class="probe"><span>IPv6</span><span id="ipv6-status" class="loading">检测中</span></div>
          <div class="probe"><span>IPv6 地址</span><span id="ipv6-ip">等待响应...</span></div>
        </div>
        <div class="tools">
          <button id="rerun" class="button primary" type="button">重新检测</button>
          <a class="button" href="/api/check" target="_blank" rel="noreferrer">当前 JSON</a>
          <a class="button" href="/api/ip" target="_blank" rel="noreferrer">当前 IP</a>
          <a class="button" href="/api/probes" target="_blank" rel="noreferrer">探测配置</a>
        </div>
      </article>

      <article class="card">
        <p class="summary-label">Project API</p>
        <h2>接口保持兼容</h2>
        <p>首页已替换为 Portfolio Hero 视觉，原有查询参数与 API 路由仍可直接使用。</p>
        <div class="api-list">
          <code>GET /api/check - 当前连接完整 JSON</code>
          <code>GET /api/ip - 当前连接 IP 纯文本</code>
          <code>GET /api/ip?family=ipv4 - 仅 IPv4 时返回 IP</code>
          <code>GET /api/ip?family=ipv6 - 仅 IPv6 时返回 IP</code>
          <code>GET /api/ipv4 - IPv4 纯文本快捷接口</code>
          <code>GET /api/ipv6 - IPv6 纯文本快捷接口</code>
        </div>
      </article>
    </section>
  </main>

  <script id="probe-config" type="application/json"><?php echo $probeConfig ?: '{}'; ?></script>
  <script src="/assets/js/app.js" defer></script>
</body>
</html>
