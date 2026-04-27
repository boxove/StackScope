const configElement = document.getElementById('probe-config');
let targets = {};

try {
  targets = configElement ? JSON.parse(configElement.textContent || '{}') : {};
} catch {
  targets = {};
}

const $ = id => document.getElementById(id);
const PROBE_TIMEOUT_MS = 8000;

function setProbe(family, state, data = {}) {
  const status = $(`${family}-status`);
  const ip = $(`${family}-ip`);
  const ok = state === 'ok';
  status.textContent = ok ? '可用' : state === 'bad' ? '不可用' : '检测中';
  status.className = ok ? 'ok' : state === 'bad' ? 'bad' : 'loading';
  ip.textContent = data.ip || (ok ? '-' : `${family.toUpperCase()} 未连通`);
}

function setCombinedIp(results) {
  const ipv4 = results.find(data => data.version === 4 && data.ip)?.ip || '';
  const ipv6 = results.find(data => data.version === 6 && data.ip)?.ip || '';
  const text = [ipv4, ipv6].filter(Boolean).join('\n') || '未获取到有效 IP';
  const status = $('status');
  const summaryIp = document.querySelector('.summary-ip');

  status.textContent = text;
  if (summaryIp) summaryIp.textContent = text;
}

async function probe(family, expectedVersion) {
  setProbe(family, 'loading');

  if (!targets[family]) {
    const message = '探测地址未配置';
    setProbe(family, 'bad', { ip: message });
    return { error: message };
  }

  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), PROBE_TIMEOUT_MS);

  try {
    const separator = targets[family].includes('?') ? '&' : '?';
    const response = await fetch(`${targets[family]}${separator}ts=${Date.now()}`, {
      cache: 'no-store',
      signal: controller.signal
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const data = await response.json();
    setProbe(family, data.version === expectedVersion && data.ip ? 'ok' : 'bad', data);
    return data;
  } catch (error) {
    const message = error.name === 'AbortError'
      ? '请求超时：请检查 DNS、HTTPS 证书或网络连通性'
      : error instanceof TypeError ? '请求失败：请检查 DNS、HTTPS 证书、CDN 或 CORS' : (error.message || '未知错误');
    setProbe(family, 'bad', { ip: message });
    return { error: message };
  } finally {
    clearTimeout(timeoutId);
  }
}

function run() {
  Promise.all([probe('ipv4', 4), probe('ipv6', 6)]).then(setCombinedIp);
}

$('rerun').addEventListener('click', run);
run();
