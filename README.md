# IPv4 / IPv6 Dual Stack Detector

轻量 PHP 双栈检测页，可用于展示当前访问 IP，并分别探测 IPv4-only 与 IPv6-only 子域是否可用。

## 部署

1. 将项目上传到支持 PHP 8.1+ 的 Web 目录。
2. 将主域名指向该目录。
3. 配置 `ipv4.example.com` 仅解析 A 记录，`ipv6.example.com` 仅解析 AAAA 记录，并指向同一站点。
4. 如探测子域不是 `ipv4.` / `ipv6.` 前缀，在 `config.php` 中设置完整的 `custom_ipv4_probe_url` 和 `custom_ipv6_probe_url`。
5. 如果站点在 CDN 或反向代理后面，把可信来源 IP 头放到 `config.php` 的 `trusted_headers` 最前面。

## Nginx 环境

项目根目录提供了 `nginx.example.conf`，可作为 Nginx + PHP-FPM 的参考配置。

使用时需要按实际环境修改：

- `server_name`：改成你的主域、IPv4-only 子域和 IPv6-only 子域。
- `root`：改成项目部署目录。
- `fastcgi_pass`：改成当前服务器的 PHP-FPM socket 或 TCP 地址，例如 `unix:/run/php/php8.2-fpm.sock` 或 `127.0.0.1:9000`。
- HTTPS 证书路径：如果启用 443 配置，替换为实际证书路径。

Nginx 关键点：

- `/api/check`、`/api/ip`、`/api/ipv4`、`/api/ipv6`、`/api/probes` 会重写到对应 `.php` 文件。
- 其他路径通过 `try_files` 回退到 `index.php`，保留首页内置路由兼容。
- 建议同时为主域、`ipv4.`、`ipv6.` 配置同一套 TLS 证书，否则浏览器探测会因证书错误失败。

## PHP `.user.ini`

项目包含 `.user.ini`，用于共享主机或 PHP-FPM 环境下的目录级 PHP 配置。

当前配置包含：

- 关闭 PHP 版本暴露和页面错误展示。
- 开启错误日志记录。
- 设置 UTF-8 默认字符集。
- 收紧 Session Cookie 参数。
- 限制上传大小、执行时间和内存占用。

注意事项：

- `.user.ini` 是否生效取决于服务器的 `user_ini.filename` 配置。
- PHP-FPM 通常会按 `user_ini.cache_ttl` 缓存 `.user.ini`，修改后可能不会立刻生效。
- `expose_php` 等部分指令在某些环境中可能无法通过 `.user.ini` 覆盖，需要在主 `php.ini` 或虚拟主机配置中设置。

## 接口

- `GET /api/check`：返回当前连接完整 JSON。
- `GET /api/ip`：返回当前连接 IP 纯文本。
- `GET /api/ip?family=ipv4`：仅在当前连接为 IPv4 时返回 IP。
- `GET /api/ip?family=ipv6`：仅在当前连接为 IPv6 时返回 IP。
- `GET /api/ipv4`：IPv4 纯文本快捷接口。
- `GET /api/ipv6`：IPv6 纯文本快捷接口。
- `GET /api/probes`：返回页面使用的探测地址。

## 注意

- 页面探测依赖浏览器能访问 `ipv4.` 与 `ipv6.` 子域，DNS、证书、CDN 回源和 CORS 都需要同时配置正确。
- 默认启用跨域读取接口，便于不同探测子域之间互相请求。
- `.htaccess` 已包含 Apache 路由与基础安全响应头；Nginx 可参考 `nginx.example.conf`。
- `.user.ini` 提供 PHP 运行参数兜底；生产环境仍建议在主 `php.ini` 或 PHP-FPM pool 中统一配置。
