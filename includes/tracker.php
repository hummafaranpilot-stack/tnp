<?php
/**
 * Pageview tracker for the public offers directory.
 *
 * log_visit() is called at the top of index.php; it inserts a row into
 * `visits` with the server-side fields (ip, ua, page, referrer, device,
 * country) and returns the visit_id so the template can expose it to
 * tracker.js for the client-side beacon.
 */

require_once __DIR__ . '/db.php';

function log_visit(): array {
    try {
        $pdo = get_pdo();
    } catch (Throwable $e) {
        return ['visit_id' => 0, 'session_token' => ''];
    }

    // Preserve (or create) a per-visitor session token via cookie so
    // repeat pageviews group under the same "session".
    $token = $_COOKIE['tnp_visitor'] ?? '';
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
        $token = bin2hex(random_bytes(16));
        setcookie('tnp_visitor', $token, [
            'expires'  => time() + 60 * 60 * 24 * 365,  // 1 year
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    $ip   = visitor_ip();
    $ua   = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
    $page = substr(($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://'
                 . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'), 0, 500);
    $ref  = substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 500);

    // Skip obvious bots (cheap server-side filter; not perfect but keeps
    // the dashboard from drowning in crawler noise).
    if (is_bot_ua($ua)) {
        return ['visit_id' => 0, 'session_token' => $token];
    }

    $geo = resolve_ip_country($ip);

    $stmt = $pdo->prepare("INSERT INTO visits
        (session_token, visited_at, ip_address, country_code, country_name, city,
         user_agent, device, page_url, referrer, referrer_source)
        VALUES (:tok, NOW(), :ip, :cc, :cn, :city, :ua, :dev, :page, :ref, :refsrc)");
    $stmt->execute([
        ':tok'    => $token,
        ':ip'     => $ip,
        ':cc'     => $geo['country_code'] ?? null,
        ':cn'     => $geo['country_name'] ?? null,
        ':city'   => $geo['city'] ?? null,
        ':ua'     => $ua,
        ':dev'    => parse_device($ua),
        ':page'   => $page,
        ':ref'    => $ref,
        ':refsrc' => parse_referrer_source($ref),
    ]);
    return ['visit_id' => (int)$pdo->lastInsertId(), 'session_token' => $token];
}

/** True client IP, respecting the usual proxy headers. */
function visitor_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
        $v = $_SERVER[$key] ?? '';
        if ($v === '') continue;
        // X-Forwarded-For may be "client, proxy1, proxy2" — take the first.
        $first = trim(explode(',', $v)[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) return $first;
    }
    return '0.0.0.0';
}

function is_bot_ua(string $ua): bool {
    if ($ua === '') return true; // no UA → almost always a bot
    return (bool) preg_match(
        '/bot|crawl|spider|slurp|mediapartners|facebookexternalhit|preview|curl|wget|python-requests|headless|lighthouse/i',
        $ua
    );
}

function parse_device(string $ua): string {
    if ($ua === '') return 'Unknown';
    if (preg_match('/iPad|Tablet/i', $ua)) return 'Tablet';
    if (preg_match('/Mobi|Android|iPhone|iPod|Windows Phone/i', $ua)) return 'Mobile';
    return 'Desktop';
}

/**
 * Collapse a referrer URL to a friendly source label.
 *  - empty         → Direct
 *  - *.google.*    → Google
 *  - *.facebook.*  → Facebook
 *  - *.t.co / twitter → X/Twitter
 *  - other         → bare host
 */
function parse_referrer_source(string $ref): string {
    if ($ref === '') return 'Direct';
    $host = parse_url($ref, PHP_URL_HOST) ?: '';
    $host = strtolower($host);
    if ($host === '') return 'Direct';
    $rules = [
        '/(^|\.)google\./'              => 'Google',
        '/(^|\.)bing\./'                => 'Bing',
        '/(^|\.)duckduckgo\./'          => 'DuckDuckGo',
        '/(^|\.)yahoo\./'               => 'Yahoo',
        '/(^|\.)facebook\./'            => 'Facebook',
        '/(^|\.)instagram\./'           => 'Instagram',
        '/(^|\.)(twitter|x)\./'         => 'X / Twitter',
        '/(^|\.)t\.co$/'                => 'X / Twitter',
        '/(^|\.)youtube\./'             => 'YouTube',
        '/(^|\.)tiktok\./'              => 'TikTok',
        '/(^|\.)reddit\./'              => 'Reddit',
        '/(^|\.)linkedin\./'            => 'LinkedIn',
        '/(^|\.)t\.me$/'                => 'Telegram',
        '/(^|\.)telegram\./'            => 'Telegram',
        '/(^|\.)wa\.me$/'               => 'WhatsApp',
        '/(^|\.)whatsapp\./'            => 'WhatsApp',
        '/(^|\.)trustednutraproduct\./' => 'Internal',
    ];
    foreach ($rules as $re => $label) {
        if (preg_match($re, $host)) return $label;
    }
    return $host;
}

/**
 * IP → country lookup with DB cache. On cache miss calls ip-api.com's free
 * endpoint (HTTP, rate-limited ~45 req/min). Failures fall back to NULL
 * so we never block a pageview on the external service.
 */
function resolve_ip_country(string $ip): array {
    if (!filter_var($ip, FILTER_VALIDATE_IP) || in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'], true)) {
        return ['country_code' => null, 'country_name' => null, 'city' => null, 'region' => null];
    }
    try {
        $pdo = get_pdo();
        $row = $pdo->prepare('SELECT country_code, country_name, city, region FROM ip_geo_cache WHERE ip_address = :ip');
        $row->execute([':ip' => $ip]);
        $cached = $row->fetch();
        if ($cached) return $cached;
    } catch (Throwable $e) { /* fall through to external lookup */ }

    $ch = curl_init("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,region,regionName,city");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 2,
        CURLOPT_CONNECTTIMEOUT => 1,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    if (!$body) return ['country_code' => null, 'country_name' => null, 'city' => null, 'region' => null];

    $j = json_decode($body, true);
    if (!is_array($j) || ($j['status'] ?? '') !== 'success') {
        return ['country_code' => null, 'country_name' => null, 'city' => null, 'region' => null];
    }

    $geo = [
        'country_code' => strtolower((string)($j['countryCode'] ?? '')) ?: null,
        'country_name' => (string)($j['country'] ?? '') ?: null,
        'city'         => (string)($j['city'] ?? '') ?: null,
        'region'       => (string)($j['regionName'] ?? '') ?: null,
    ];
    try {
        $pdo->prepare('INSERT IGNORE INTO ip_geo_cache (ip_address, country_code, country_name, city, region) VALUES (:ip, :cc, :cn, :city, :region)')
            ->execute([':ip' => $ip, ':cc' => $geo['country_code'], ':cn' => $geo['country_name'], ':city' => $geo['city'], ':region' => $geo['region']]);
    } catch (Throwable $e) { /* best effort */ }
    return $geo;
}
