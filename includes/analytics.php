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
    $url = trim($d['domain_url'] ?? '');
    $landers = $url !== '' ? [['label' => 'Main Lander', 'url' => $url]] : [];
    return [
        'id'                 => null,
        'sr'                 => 0, // server will auto-assign
        'offer_name'         => $d['label'] ?? '',
        'platform'           => normalize_platform($d['platform'] ?? ''),
        'affiliate_page_url' => $url,
        'offer_id'           => '',
        'category'           => '',
        'revshare'           => '',
        'cpa'                => '',
        'allowed_geos'       => 'Tier-1',
        'restriction'        => 'No',
        'image_url'          => '',
        'top_landers'        => $landers,
    ];
}
