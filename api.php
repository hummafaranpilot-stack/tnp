<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

header('Content-Type: application/json');
require_login_api();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = get_pdo();

    if ($method === 'POST' && $action === 'create') {
        $data = read_json();
        $stmt = $pdo->prepare("INSERT INTO offers
            (sr, platform, offer_name, offer_id, category, top_landers,
             affiliate_page_url, revshare, cpa, allowed_geos, restriction)
            VALUES (:sr, :platform, :offer_name, :offer_id, :category, :top_landers,
                    :affiliate_page_url, :revshare, :cpa, :allowed_geos, :restriction)");
        $stmt->execute(bind($data));
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($method === 'POST' && $action === 'update') {
        $data = read_json();
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('Missing id');
        $stmt = $pdo->prepare("UPDATE offers SET
            sr = :sr, platform = :platform, offer_name = :offer_name, offer_id = :offer_id,
            category = :category, top_landers = :top_landers, affiliate_page_url = :affiliate_page_url,
            revshare = :revshare, cpa = :cpa, allowed_geos = :allowed_geos, restriction = :restriction
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
    return [
        ':sr' => (int)($d['sr'] ?? 0),
        ':platform' => (string)($d['platform'] ?? ''),
        ':offer_name' => (string)($d['offer_name'] ?? ''),
        ':offer_id' => (string)($d['offer_id'] ?? ''),
        ':category' => (string)($d['category'] ?? ''),
        ':top_landers' => json_encode($landers),
        ':affiliate_page_url' => (string)($d['affiliate_page_url'] ?? ''),
        ':revshare' => (string)($d['revshare'] ?? ''),
        ':cpa' => (string)($d['cpa'] ?? ''),
        ':allowed_geos' => (string)($d['allowed_geos'] ?? ''),
        ':restriction' => (string)($d['restriction'] ?? 'No'),
    ];
}
