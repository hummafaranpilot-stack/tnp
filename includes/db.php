<?php
if (!file_exists(__DIR__ . '/../config.php')) {
    render_missing_config();
    exit;
}
require_once __DIR__ . '/../config.php';

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        ensure_schema($pdo);
    }
    return $pdo;
}

function ensure_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS offers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sr INT NOT NULL DEFAULT 0,
        platform VARCHAR(100) DEFAULT '',
        offer_name VARCHAR(255) DEFAULT '',
        image_url VARCHAR(500) DEFAULT '',
        offer_id VARCHAR(100) DEFAULT '',
        category VARCHAR(100) DEFAULT '',
        top_landers TEXT,
        affiliate_page_url VARCHAR(500) DEFAULT '',
        revshare VARCHAR(50) DEFAULT '',
        cpa VARCHAR(100) DEFAULT '',
        allowed_geos VARCHAR(100) DEFAULT '',
        restriction VARCHAR(10) DEFAULT 'No',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    // Add image_url column if upgrading from older schema
    $cols = $pdo->query("SHOW COLUMNS FROM offers LIKE 'image_url'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE offers ADD COLUMN image_url VARCHAR(500) DEFAULT '' AFTER offer_name");
    }

    // Track which Shaver suggestion produced this offer (so rename-in-TNP
    // doesn't re-show the suggestion next time)
    $cols = $pdo->query("SHOW COLUMNS FROM offers LIKE 'shaver_domain_id'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE offers ADD COLUMN shaver_domain_id INT NULL DEFAULT NULL");
        $pdo->exec("CREATE INDEX idx_offers_shaver_domain_id ON offers(shaver_domain_id)");
    }

    // Arbitrary titled links per offer (Affiliate Page / Creatives / Traffic Tips / Ad Copy Swipes)
    $cols = $pdo->query("SHOW COLUMNS FROM offers LIKE 'links'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE offers ADD COLUMN links TEXT NULL AFTER affiliate_page_url");
    }

    // Traffic tips (bulleted list shown next to each offer)
    $cols = $pdo->query("SHOW COLUMNS FROM offers LIKE 'traffic_tips'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE offers ADD COLUMN traffic_tips TEXT NULL AFTER restriction");
    }

    // Other pages — secondary landing pages admin adds from Shaver suggestions
    $cols = $pdo->query("SHOW COLUMNS FROM offers LIKE 'other_pages'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE offers ADD COLUMN other_pages TEXT NULL AFTER top_landers");
    }

    // Dismissed Shaver domain suggestions
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS dismissed_shaver_domains (
        shaver_domain_id INT PRIMARY KEY,
        dismissed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $done = true;
}

function next_sr(PDO $pdo): int {
    $row = $pdo->query('SELECT COALESCE(MAX(sr), 0) + 1 AS next_sr FROM offers')->fetch();
    return (int)($row['next_sr'] ?? 1);
}

function render_missing_config(): void {
    http_response_code(503);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>TNP — Setup needed</title>';
    echo '<style>body{font-family:system-ui;max-width:640px;margin:3rem auto;padding:2rem;color:#1f2937;line-height:1.6}';
    echo 'h1{color:#0f1a35}code{background:#f3f4f6;padding:2px 6px;border-radius:4px;font-size:.9em}';
    echo 'pre{background:#0f1a35;color:#f9fafb;padding:1rem;border-radius:.5rem;overflow-x:auto;font-size:.85em}';
    echo '.box{background:#fef3c7;border:1px solid #fcd34d;padding:1rem;border-radius:.5rem;margin:1rem 0}</style>';
    echo '</head><body>';
    echo '<h1>⚙ Setup required</h1>';
    echo '<p>This site needs a <code>config.php</code> file with database credentials before it can run.</p>';
    echo '<div class="box"><strong>What to do:</strong><ol>';
    echo '<li>Open Hostinger <strong>File Manager</strong> → <code>public_html/</code></li>';
    echo '<li>Create a new file named <code>config.php</code></li>';
    echo '<li>Paste the contents from <code>config.sample.php</code> and fill in your MySQL credentials</li>';
    echo '<li>Reload this page</li>';
    echo '</ol></div>';
    echo '<p>See <code>config.sample.php</code> in the repo for the exact format.</p>';
    echo '</body></html>';
}
