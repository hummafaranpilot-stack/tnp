<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/analytics.php';
require __DIR__ . '/includes/images.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Most endpoints return JSON; a few stream other content (multipart
// upload, CSV export) and set their own Content-Type later.
if (!in_array($action, ['upload', 'export_offers'], true)) {
    header('Content-Type: application/json');
}
// top_landers is safe to expose to unauthenticated viewers — it only
// returns aggregate landing-URL data used by the Promote Now modal.
$PUBLIC_ACTIONS = ['top_landers', 'track', 'export_offers'];
if (!in_array($action, $PUBLIC_ACTIONS, true)) {
    require_login_api();
}

try {
    $pdo = get_pdo();

    if ($method === 'POST' && $action === 'create') {
        $data = read_json();
        $sr = (int)($data['sr'] ?? 0);
        if ($sr <= 0) $sr = next_sr($pdo);

        $stmt = $pdo->prepare("INSERT INTO offers
            (sr, platform, offer_name, image_url, offer_id, category, top_landers, other_pages,
             affiliate_page_url, links, clickbank_redirect_url, revshare, cpa, cpa_manual, allowed_geos, restriction,
             traffic_tips, coming_soon, shaver_domain_id)
            VALUES (:sr, :platform, :offer_name, :image_url, :offer_id, :category, :top_landers, :other_pages,
                    :affiliate_page_url, :links, :clickbank_redirect_url, :revshare, :cpa, :cpa_manual, :allowed_geos, :restriction,
                    :traffic_tips, :coming_soon, :shaver_domain_id)");
        $params = bind($data);
        $params[':sr'] = $sr;
        $params[':shaver_domain_id'] = !empty($data['shaver_domain_id']) ? (int)$data['shaver_domain_id'] : null;
        $stmt->execute($params);
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($method === 'POST' && $action === 'update') {
        $data = read_json();
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('Missing id');
        $stmt = $pdo->prepare("UPDATE offers SET
            sr = :sr, platform = :platform, offer_name = :offer_name, image_url = :image_url,
            offer_id = :offer_id, category = :category, top_landers = :top_landers, other_pages = :other_pages,
            affiliate_page_url = :affiliate_page_url, links = :links,
            clickbank_redirect_url = :clickbank_redirect_url,
            revshare = :revshare, cpa = :cpa, cpa_manual = :cpa_manual,
            allowed_geos = :allowed_geos, restriction = :restriction, traffic_tips = :traffic_tips,
            coming_soon = :coming_soon
            WHERE id = :id");
        $params = bind($data);
        $params[':id'] = $id;
        $stmt->execute($params);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($method === 'POST' && $action === 'delete') {
        $data = read_json();
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('Missing id');
        $stmt = $pdo->prepare('DELETE FROM offers WHERE id = :id');
        $stmt->execute([':id' => $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($method === 'POST' && $action === 'upload') {
        header('Content-Type: application/json');
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No file uploaded or upload error');
        }
        $file = $_FILES['image'];
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new RuntimeException('File too large (max 10 MB)');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Invalid image type (only jpg, png, webp, gif)');
        }
        $ext = $allowed[$mime];
        $uploads_dir = __DIR__ . '/uploads';
        if (!is_dir($uploads_dir)) {
            @mkdir($uploads_dir, 0755, true);
        }
        $name = bin2hex(random_bytes(12)) . '.' . $ext;
        $dest = $uploads_dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Failed to save uploaded file');
        }
        // Auto-compress: cap longest side at 800 px and re-encode at q=82.
        // Original product photos are often 3-4 MB which times out on slow
        // mobile networks even though the thumbnail renders at ~80 px.
        $cmp = compress_image($dest);
        echo json_encode([
            'ok'          => true,
            'url'         => '/uploads/' . $name,
            'compressed'  => $cmp['ok'] ?? false,
            'before_kb'   => isset($cmp['before']) ? (int)round($cmp['before'] / 1024) : null,
            'after_kb'    => isset($cmp['after'])  ? (int)round($cmp['after'] / 1024)  : null,
            'reason'      => $cmp['reason'] ?? ($cmp['error'] ?? null),
        ]);
        exit;
    }

    if ($method === 'POST' && $action === 'optimize_uploads') {
        // One-shot batch compressor for existing files in /uploads/.
        // Re-encodes every oversized image in place (filenames don't change)
        // so already-cached references keep working post-cleanup.
        $r = compress_all_uploads(__DIR__ . '/uploads');
        echo json_encode($r);
        exit;
    }

    if ($method === 'GET' && $action === 'export_offers') {
        // Stream a CSV (UTF-8 with BOM so Excel renders unicode correctly)
        // containing every offer + all linked metadata, semicolon-joined for
        // arrays so a single cell carries the full list.
        $rows = $pdo->query('SELECT * FROM offers ORDER BY sr ASC')->fetchAll();

        $filename = 'tnp-offers-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM tells Excel to read the file as UTF-8 instead of cp1252.
        fwrite($out, "\xEF\xBB\xBF");

        $headers = [
            'Sr', 'Platform', 'Offer Name', 'Offer ID / Nickname', 'Category',
            'Coming Soon', 'Image URL',
            'RevShare', 'CPA', 'CPA Manually Activated',
            'Allowed GEOs', 'Restriction',
            'Affiliate Page', 'Creatives', 'Traffic Tips Link', 'Ad Copy Swipes',
            'ClickBank Redirect URL',
            'Top Landers', 'Other Pages', 'Traffic Tips',
            'Created At', 'Updated At',
        ];
        fputcsv($out, $headers);

        foreach ($rows as $o) {
            // Pull named links out of the JSON array so each well-known
            // title gets its own column. Anything else stays in the
            // "Other Pages" cell so nothing is lost.
            $links = json_decode($o['links'] ?? '[]', true) ?: [];
            $named = ['Affiliate Page' => '', 'Creatives' => '', 'Traffic Tips' => '', 'Ad Copy Swipes' => ''];
            foreach ($links as $ln) {
                $title = (string)($ln['title'] ?? '');
                $url   = (string)($ln['url'] ?? '');
                if (isset($named[$title]) && $url !== '') $named[$title] = $url;
            }

            $landers = json_decode($o['top_landers'] ?? '[]', true) ?: [];
            $landers_str = implode(' | ', array_filter(array_map(function ($l) {
                $label  = trim((string)($l['label'] ?? ''));
                $url    = trim((string)($l['url'] ?? ''));
                $advice = trim((string)($l['advice'] ?? ''));
                if ($url === '') return '';
                $prefix = $label !== '' ? $label : 'Lander';
                if ($advice !== '') $prefix .= " ({$advice})";
                return $prefix . ' → ' . $url;
            }, $landers)));

            $others = json_decode($o['other_pages'] ?? '[]', true) ?: [];
            $others_str = implode(' | ', array_filter(array_map(function ($p) {
                $label = trim((string)($p['label'] ?? ''));
                $url   = trim((string)($p['url'] ?? ''));
                if ($url === '') return '';
                return ($label !== '' ? $label : 'Page') . ' → ' . $url;
            }, $others)));

            $tips = json_decode($o['traffic_tips'] ?? '[]', true) ?: [];
            $tips_str = implode(' | ', array_filter(array_map(function ($t) {
                if (is_array($t)) {
                    $lbl = trim((string)($t['label'] ?? ''));
                    $val = trim((string)($t['value'] ?? ''));
                    if ($val === '') return '';
                    return ($lbl !== '' ? "{$lbl}: " : '') . $val;
                }
                return is_string($t) ? trim($t) : '';
            }, $tips)));

            fputcsv($out, [
                (string)($o['sr'] ?? ''),
                (string)($o['platform'] ?? ''),
                (string)($o['offer_name'] ?? ''),
                (string)($o['offer_id'] ?? ''),
                (string)($o['category'] ?? ''),
                !empty($o['coming_soon']) ? 'Yes' : 'No',
                (string)($o['image_url'] ?? ''),
                (string)($o['revshare'] ?? ''),
                (string)($o['cpa'] ?? ''),
                !empty($o['cpa_manual']) ? 'Yes' : 'No',
                (string)($o['allowed_geos'] ?? ''),
                (string)($o['restriction'] ?? ''),
                $named['Affiliate Page'],
                $named['Creatives'],
                $named['Traffic Tips'],
                $named['Ad Copy Swipes'],
                (string)($o['clickbank_redirect_url'] ?? ''),
                $landers_str,
                $others_str,
                $tips_str,
                (string)($o['created_at'] ?? ''),
                (string)($o['updated_at'] ?? ''),
            ]);
        }
        fclose($out);
        exit;
    }

    if ($method === 'GET' && $action === 'next_sr') {
        echo json_encode(['sr' => next_sr($pdo)]);
        exit;
    }

    if ($method === 'POST' && $action === 'top_landers') {
        $data = read_json();
        $domain_id = (int)($data['domain_id'] ?? 0);
        $limit = (int)($data['limit'] ?? 5);
        if ($limit < 1 || $limit > 1000) $limit = 5;
        $result = shaver_fetch_top_landers($domain_id, $limit);
        echo json_encode($result);
        exit;
    }

    if ($method === 'POST' && $action === 'reorder') {
        $data = read_json();
        $ids = $data['ids'] ?? [];
        if (!is_array($ids) || empty($ids)) throw new RuntimeException('Missing ids');
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE offers SET sr = :sr WHERE id = :id');
        $sr = 1;
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id <= 0) continue;
            $stmt->execute([':sr' => $sr, ':id' => $id]);
            $sr++;
        }
        $pdo->commit();
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($method === 'POST' && $action === 'track') {
        $data = read_json();
        $visit_id = (int)($data['visit_id'] ?? 0);
        if ($visit_id <= 0) { echo json_encode(['ok' => false]); exit; }
        // Only accept updates for rows created in the last 6 hours — this
        // stops someone from replaying a stale visit_id to inject clicks
        // into old analytics data.
        $row = $pdo->prepare('SELECT id FROM visits WHERE id = :id AND visited_at > DATE_SUB(NOW(), INTERVAL 6 HOUR)');
        $row->execute([':id' => $visit_id]);
        if (!$row->fetchColumn()) { echo json_encode(['ok' => false, 'error' => 'stale']); exit; }

        $max_scroll = isset($data['max_scroll']) ? max(0, min(100, (int)$data['max_scroll'])) : null;
        $duration = isset($data['duration_sec']) ? max(0, min(86400, (int)$data['duration_sec'])) : null;
        $clicks = $data['clicks'] ?? [];
        if (!is_array($clicks)) $clicks = [];
        // Cap clicks JSON to something reasonable so a buggy client can't bloat the row.
        if (count($clicks) > 100) $clicks = array_slice($clicks, 0, 100);

        $stmt = $pdo->prepare('UPDATE visits SET max_scroll = :s, duration_sec = :d, clicks_json = :c, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':s'  => $max_scroll,
            ':d'  => $duration,
            ':c'  => json_encode($clicks),
            ':id' => $visit_id,
        ]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($method === 'POST' && $action === 'dismiss_suggestion') {
        $data = read_json();
        $domain_id = (int)($data['shaver_domain_id'] ?? 0);
        if ($domain_id <= 0) throw new RuntimeException('Missing shaver_domain_id');
        $stmt = $pdo->prepare('INSERT IGNORE INTO dismissed_shaver_domains (shaver_domain_id) VALUES (:id)');
        $stmt->execute([':id' => $domain_id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function read_json(): array {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function bind(array $d): array {
    $landers = $d['top_landers'] ?? [];
    if (!is_array($landers)) $landers = [];
    $other_pages = $d['other_pages'] ?? [];
    if (!is_array($other_pages)) $other_pages = [];
    $links = $d['links'] ?? [];
    if (!is_array($links)) $links = [];
    $platform = (string)($d['platform'] ?? '');
    $cb_url = trim((string)($d['clickbank_redirect_url'] ?? ''));
    $coming_soon = !empty($d['coming_soon']) ? 1 : 0;
    // Server-side guard: ClickBank offers must carry a redirect URL
    // (skipped while coming_soon is on — the admin is creating a placeholder)
    if (!$coming_soon && strcasecmp($platform, 'ClickBank') === 0 && $cb_url === '') {
        throw new RuntimeException('ClickBank offers require a redirect URL (admin → Basics → ClickBank Redirect URL).');
    }
    $tips_raw = $d['traffic_tips'] ?? [];
    if (!is_array($tips_raw)) $tips_raw = [];
    $tips = [];
    foreach ($tips_raw as $t) {
        if (is_array($t) && !empty($t['label'])) {
            $val = trim((string)($t['value'] ?? ''));
            if ($val !== '') $tips[] = ['label' => (string)$t['label'], 'value' => $val];
        } elseif (is_string($t) && trim($t) !== '') {
            $tips[] = ['label' => 'Note', 'value' => trim($t)];
        }
    }
    // Keep affiliate_page_url in sync with the first "Affiliate Page" entry
    // (backwards compatibility for code that still reads that column).
    $aff_url = (string)($d['affiliate_page_url'] ?? '');
    foreach ($links as $ln) {
        if (($ln['title'] ?? '') === 'Affiliate Page' && !empty($ln['url'])) {
            $aff_url = (string)$ln['url'];
            break;
        }
    }
    return [
        ':sr' => (int)($d['sr'] ?? 0),
        ':platform' => (string)($d['platform'] ?? ''),
        ':offer_name' => (string)($d['offer_name'] ?? ''),
        ':image_url' => (string)($d['image_url'] ?? ''),
        ':offer_id' => (string)($d['offer_id'] ?? ''),
        ':category' => (string)($d['category'] ?? ''),
        ':top_landers' => json_encode($landers),
        ':other_pages' => json_encode($other_pages),
        ':affiliate_page_url' => $aff_url,
        ':links' => json_encode($links),
        ':clickbank_redirect_url' => $cb_url,
        ':revshare' => (string)($d['revshare'] ?? ''),
        ':cpa' => (string)($d['cpa'] ?? ''),
        ':cpa_manual' => !empty($d['cpa_manual']) ? 1 : 0,
        ':allowed_geos' => (string)($d['allowed_geos'] ?? ''),
        ':restriction' => (string)($d['restriction'] ?? 'No'),
        ':traffic_tips' => json_encode($tips),
        ':coming_soon' => $coming_soon,
    ];
}
