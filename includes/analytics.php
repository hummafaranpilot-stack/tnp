<?php
require_once __DIR__ . '/../config.php';

/**
 * Fetch active domains from the Shaver analytics API.
 * Cached on disk for 5 minutes to avoid hammering the upstream.
 *
 * @return array{ok: bool, domains: array, error: ?string}
 */
function shaver_fetch_domains(): array {
    if (!defined('SHAVER_API_KEY') || SHAVER_API_KEY === '') {
        return ['ok' => false, 'domains' => [], 'error' => 'not_configured'];
    }

    $cache_file = sys_get_temp_dir() . '/tnp_shaver_domains.json';
    $cache_ttl = 300; // 5 minutes

    if (is_file($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl) {
        $cached = @file_get_contents($cache_file);
        $decoded = $cached ? json_decode($cached, true) : null;
        if (is_array($decoded) && !empty($decoded['ok'])) {
            return $decoded;
        }
    }

    $url = SHAVER_API_URL . '?r=domains';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . SHAVER_API_KEY],
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($body === false || $http_code >= 400) {
        return ['ok' => false, 'domains' => [], 'error' => $err ?: ('HTTP ' . $http_code)];
    }

    $data = json_decode($body, true);
    if (!is_array($data) || empty($data['ok'])) {
        return ['ok' => false, 'domains' => [], 'error' => $data['error'] ?? 'Unexpected response'];
    }

    $domains = array_values(array_filter($data['data'] ?? [], fn($d) => ($d['status'] ?? '') === 'active'));

    $result = ['ok' => true, 'domains' => $domains, 'error' => null];
    @file_put_contents($cache_file, json_encode($result));
    return $result;
}

/**
 * Convert Shaver's lowercase platform slug to the capitalized form
 * used in our offers.platform column.
 */
function normalize_platform(string $p): string {
    return match (strtolower($p)) {
        'buygoods'    => 'BuyGoods',
        'clickbank'   => 'ClickBank',
        'digistore24' => 'Digistore24',
        'maxweb'      => 'MaxWeb',
        default       => ucfirst($p),
    };
}

/**
 * Map a Shaver domain row to the shape our openForm(offer) JS expects.
 */
function shaver_domain_to_offer(array $d): array {
    // Shaver's `label` meaning depends on the platform:
    //   BuyGoods  → Product Title  → offer_name
    //   ClickBank → Nickname       → offer_id
    // For any other platform, treat it as a generic offer_id.
    //
    // NOTE: We intentionally leave affiliate_page_url / links empty.
    // The domain_url is just the tracked domain's base and is NOT a real
    // affiliate tools URL. The async fetchTopLandersAsync() step scans
    // Shaver traffic for URLs containing "afftools"/"affiliate" — if none
    // are found, we would rather leave the field blank than seed a wrong
    // URL. The admin can fill it in manually if needed.
    $platform = normalize_platform($d['platform'] ?? '');
    $label    = $d['label'] ?? '';
    $is_bg    = strcasecmp($platform, 'BuyGoods') === 0;

    return [
        'id'                 => null,
        'shaver_domain_id'   => (int)($d['id'] ?? 0),
        'sr'                 => 0, // server will auto-assign
        'offer_name'         => $is_bg ? $label : '',
        'offer_id'           => $is_bg ? '' : $label,
        'platform'           => $platform,
        'affiliate_page_url' => '',
        'links'              => [],
        'category'           => '',
        'revshare'           => '',
        'cpa'                => '',
        'allowed_geos'       => 'Tier-1 Default',
        'restriction'        => 'No',
        'image_url'          => '',
        'top_landers'        => [], // fetched lazily via /api.php?action=top_landers
    ];
}

/**
 * Fetch top landing URLs for a domain, aggregated from the traffic log
 * across ALL TIME. URLs containing "afftools" or "affiliate" are split
 * out as the affiliate/creative page URL (most-visited wins). Tracking
 * params are stripped so variants collapse. Cached 15 min per domain.
 *
 * @return array{ok: bool, affiliate_url: ?string, landers: array, error: ?string}
 */
function shaver_fetch_top_landers(int $domain_id, int $limit = 5): array {
    if (!defined('SHAVER_API_KEY') || SHAVER_API_KEY === '' || $domain_id <= 0) {
        return ['ok' => false, 'affiliate_url' => null, 'landers' => [], 'error' => 'not_configured'];
    }

    $cache_file = sys_get_temp_dir() . "/tnp_shaver_landers_{$domain_id}.json";
    $cache_ttl = 900; // 15 min
    if (is_file($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl) {
        $cached = @file_get_contents($cache_file);
        $decoded = $cached ? json_decode($cached, true) : null;
        if (is_array($decoded) && !empty($decoded['ok'])) {
            return $decoded;
        }
    }

    // All-time window: from 2020 through today.
    $url = SHAVER_API_URL . '?r=traffic&domain_id=' . $domain_id
         . '&from=2020-01-01&to=' . date('Y-m-d') . '&limit=1000';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . SHAVER_API_KEY],
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($body === false || $http_code >= 400) {
        return ['ok' => false, 'affiliate_url' => null, 'landers' => [], 'error' => $err ?: ('HTTP ' . $http_code)];
    }

    $data = json_decode($body, true);
    if (!is_array($data) || empty($data['ok'])) {
        return ['ok' => false, 'affiliate_url' => null, 'landers' => [], 'error' => $data['error'] ?? 'Unexpected response'];
    }

    $lander_counts = [];
    $affiliate_counts = [];

    foreach ($data['data'] ?? [] as $row) {
        if (($row['page_type'] ?? '') !== 'landing') continue;
        $raw = trim($row['page_url'] ?? '');
        if ($raw === '') continue;
        $normalized = normalize_landing_url($raw);
        if ($normalized === '') continue;

        // URLs containing "afftools" or "affiliate" are treated as the
        // affiliate/creative page — not as customer landers.
        if (preg_match('/afftools|affiliate/i', $normalized)) {
            $affiliate_counts[$normalized] = ($affiliate_counts[$normalized] ?? 0) + 1;
            continue;
        }

        $lander_counts[$normalized] = ($lander_counts[$normalized] ?? 0) + 1;
    }

    arsort($lander_counts);
    arsort($affiliate_counts);

    $affiliate_url = array_key_first($affiliate_counts); // null if empty

    $landers = [];
    $i = 0;
    foreach ($lander_counts as $url => $visits) {
        if ($i >= $limit) break;
        $info = lander_info_from_url($url);
        $landers[] = [
            'label'  => $info['label'],
            'type'   => $info['type'],
            'advice' => $info['advice'],
            'url'    => $url,
            'visits' => $visits,
        ];
        $i++;
    }

    $result = [
        'ok'            => true,
        'affiliate_url' => $affiliate_url,
        'landers'       => $landers,
        'error'         => null,
    ];
    @file_put_contents($cache_file, json_encode($result));
    return $result;
}

function normalize_landing_url(string $url): string {
    $parsed = @parse_url($url);
    if (!$parsed || empty($parsed['host'])) return '';
    $scheme = $parsed['scheme'] ?? 'https';
    $host = $parsed['host'];
    $path = $parsed['path'] ?? '/';
    // Collapse /index.html, /index.php to the directory
    $path = preg_replace('#/index\.(html?|php)$#i', '/', $path);
    // Remove trailing slash unless root
    if ($path !== '/' && str_ends_with($path, '/')) {
        $path = rtrim($path, '/');
    }
    return $scheme . '://' . $host . $path;
}

/**
 * Classify a landing URL by keywords in the path. Returns a clean
 * display label, a type slug, the short advice, and a longer
 * description for hover tooltips.
 *
 *   short* → medium-length page, pair with a prelander
 *   long*  → long TSL; can direct-link, no prelander needed
 *   best*  → long-form "best" variant; treated the same as long
 *   dtc*   → direct-to-consumer pricing page, pair with a prelander
 *
 * @return array{label: string, type: string, advice: string, description: string}
 */
function lander_info_from_url(string $url): array {
    $path = parse_url($url, PHP_URL_PATH) ?? '/';
    $lower = strtolower($path);

    if (preg_match('#/dtc(\d*)(?:/|$)#', $lower, $m)) {
        return [
            'label'       => 'DTC' . $m[1],
            'type'        => 'dtc',
            'advice'      => 'prelander',
            'description' => 'Direct-to-consumer page with pricing cards and minimal content. Best paired with a prelander — the prelander warms the customer up, then they land here ready to pick a pack and buy.',
        ];
    }
    if (preg_match('#/(long|best)(\d*)(?:/|$)#', $lower, $m)) {
        return [
            'label'       => ucfirst($m[1]) . ' TSL' . $m[2],
            'type'        => 'long',
            'advice'      => 'direct-link',
            'description' => 'Long-form sales page (Text Sales Letter) with full copy, graphics, and videos. It already does the "brainwashing" on its own — no prelander needed. Direct-link your traffic straight here.',
        ];
    }
    if (preg_match('#/short(\d*)(?:/|$)#', $lower, $m)) {
        return [
            'label'       => 'Short TSL' . $m[1],
            'type'        => 'short',
            'advice'      => 'prelander',
            'description' => 'Medium-length page — lighter copy than a full TSL. Works best with a prelander that hooks the reader first, so cold traffic gets warmed up before landing here.',
        ];
    }

    // Fallback: pretty-print the path
    $clean = trim($path, '/');
    if ($clean === '') {
        return ['label' => 'Main Lander', 'type' => 'other', 'advice' => '', 'description' => ''];
    }
    $parts = array_map('ucfirst', array_filter(explode('/', $clean)));
    return [
        'label'       => implode(' / ', $parts) ?: 'Lander',
        'type'        => 'other',
        'advice'      => '',
        'description' => '',
    ];
}
