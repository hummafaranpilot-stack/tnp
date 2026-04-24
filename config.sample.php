<?php
// Copy this file to config.php and fill in your credentials.
// config.php is gitignored so it stays on Hostinger only.

// MySQL credentials (from Hostinger hPanel → Databases → MySQL Databases)
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_tnp');
define('DB_USER', 'u123456789_tnp');
define('DB_PASS', 'your-db-password');

// Admin password for /admin dashboard
define('ADMIN_PASSWORD', 'change-me-to-strong-password');

// Shaver analytics API (optional — enables domain suggestions in admin)
// Create from: https://shaver.trustednutraproduct.com/api.html
// Leave empty to disable the Suggestions feature.
define('SHAVER_API_KEY', '');
define('SHAVER_API_URL', 'https://shaver.trustednutraproduct.com/api-v1.php');
