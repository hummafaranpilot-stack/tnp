<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/analytics.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Upload endpoint returns JSON but is multipart, not application/json
if ($action !== 'upload') {
    header('Content-Type: application/json');
}
require_login_api();

try {
    $pdo = get_pdo();

    if ($method === 'POST' && $action === 'create') {
        $data = read_json();
        $sr = (int)($data['sr'] ?? 0);
        if ($sr <= 0) $sr = next_sr($pdo);

        $stmt = $pdo->prepare("INSERT INTO offers
            (sr, platform, offer_name, image_url, offer_id, category, top_landers,
             affiliate_page_url, links, revshare, cpa, allowed_geos, restriction, shaver_domain_id)
            VALUES (:sr, :platform, :offer_name, :image_url, :offer_id, :category, :top_landers,
                    :affiliate_page_url, :links, :revshare, :cpa, :allowed_geos, :restriction, :shaver_domain_id)");
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
            offer_id = :offer_id, category = :category, top_landers = :top_landers,
            affiliate_page_url = :affiliate_page_url, links = :links, revshare = :revshare, cpa = :cpa,
            allowed_geos = :allowed_geos, restriction = :restriction
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
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('File too large (max 5 MB)');
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
        echo json_encode(['ok' => true, 'url' => '/uploads/' . $name]);
        exit;
    }

    if ($method === 'GET' && $action === 'next_sr') {
        echo json_encode(['sr' => next_sr($pdo)]);
        exit;
    }

    if ($method === 'POST' && $action === 'top_landers') {
        $data = read_json();
        $domain_id = (int)($data['domain_id'] ?? 0);
        $result = shaver_fetch_top_landers($domain_id);
        echo json_encode($result);
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
    $links = $d['links'] ?? [];
    if (!is_array($links)) $links = [];
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
        ':affiliate_page_url' => $aff_url,
        ':links' => json_encode($links),
        ':revshare' => (string)($d['revshare'] ?? ''),
        ':cpa' => (string)($d['cpa'] ?? ''),
        ':allowed_geos' => (string)($d['allowed_geos'] ?? ''),
        ':restriction' => (string)($d['restriction'] ?? 'No'),
    ];
}
