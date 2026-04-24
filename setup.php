<?php
// One-time setup — run once to create the offers table, then delete this file.
// Visit: https://trustednutraproduct.com/setup.php

require __DIR__ . '/includes/db.php';

try {
    $pdo = get_pdo();

    $sql = "
    CREATE TABLE IF NOT EXISTS offers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sr INT NOT NULL DEFAULT 0,
        platform VARCHAR(100) DEFAULT '',
        offer_name VARCHAR(255) DEFAULT '',
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
    ";

    $pdo->exec($sql);

    echo "<h2 style='font-family:system-ui;color:#0f1a35;'>✓ Setup complete</h2>";
    echo "<p style='font-family:system-ui;'>Table <code>offers</code> created successfully.</p>";
    echo "<p style='font-family:system-ui;color:#c00;'><strong>IMPORTANT:</strong> Delete <code>setup.php</code> from the server now for security.</p>";
    echo "<p style='font-family:system-ui;'><a href='/'>Go to viewer</a> · <a href='/admin'>Go to admin</a></p>";
} catch (Throwable $e) {
    echo "<h2 style='font-family:system-ui;color:#c00;'>Setup failed</h2>";
    echo "<pre style='font-family:monospace;background:#f5f5f5;padding:1rem;'>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p>Check your <code>config.php</code> credentials.</p>";
}
