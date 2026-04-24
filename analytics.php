<?php
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/includes/db.php';

$pdo = get_pdo();

// Filters
$range   = $_GET['range']   ?? '7d';
$device  = trim((string)($_GET['device']  ?? ''));
$country = trim((string)($_GET['country'] ?? ''));
$source  = trim((string)($_GET['source']  ?? ''));
$page    = max(1, (int)($_GET['p'] ?? 1));
$per     = 50;

$from_sql = match ($range) {
    'today' => "DATE_SUB(NOW(), INTERVAL 24 HOUR)",
    '7d'    => "DATE_SUB(NOW(), INTERVAL 7 DAY)",
    '30d'   => "DATE_SUB(NOW(), INTERVAL 30 DAY)",
    '90d'   => "DATE_SUB(NOW(), INTERVAL 90 DAY)",
    'all'   => "'2000-01-01'",
    default => "DATE_SUB(NOW(), INTERVAL 7 DAY)",
};

$where = ["visited_at >= $from_sql"];
$bind  = [];
if ($device !== '')  { $where[] = 'device = :device';       $bind[':device']  = $device; }
if ($country !== '') { $where[] = 'country_code = :cc';     $bind[':cc']      = $country; }
if ($source !== '')  { $where[] = 'referrer_source = :src'; $bind[':src']     = $source; }
$where_sql = 'WHERE ' . implode(' AND ', $where);

// Summary stats (for the header cards)
$stats = $pdo->prepare("SELECT
    COUNT(*) AS visits,
    COUNT(DISTINCT session_token) AS sessions,
    COUNT(DISTINCT ip_address) AS unique_ips,
    ROUND(AVG(NULLIF(max_scroll, 0))) AS avg_scroll,
    ROUND(AVG(NULLIF(duration_sec, 0))) AS avg_duration
    FROM visits $where_sql");
$stats->execute($bind);
$s = $stats->fetch() ?: [];

// Total for pagination
$cnt = $pdo->prepare("SELECT COUNT(*) FROM visits $where_sql");
$cnt->execute($bind);
$total_rows = (int)$cnt->fetchColumn();
$total_pages = max(1, (int)ceil($total_rows / $per));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per;

// Rows
$sql = "SELECT * FROM visits $where_sql ORDER BY visited_at DESC LIMIT $per OFFSET $offset";
$rows = $pdo->prepare($sql);
$rows->execute($bind);
$visits = $rows->fetchAll();

// Filter option lists
$devices   = $pdo->query("SELECT DISTINCT device FROM visits WHERE device IS NOT NULL AND device != '' ORDER BY device")->fetchAll(PDO::FETCH_COLUMN);
$countries = $pdo->query("SELECT country_code, MAX(country_name) AS name, COUNT(*) c FROM visits WHERE country_code IS NOT NULL AND country_code != '' GROUP BY country_code ORDER BY c DESC LIMIT 50")->fetchAll();
$sources   = $pdo->query("SELECT DISTINCT referrer_source FROM visits WHERE referrer_source IS NOT NULL AND referrer_source != '' ORDER BY referrer_source")->fetchAll(PDO::FETCH_COLUMN);

function fmt_duration(?int $sec): string {
    if (!$sec) return '—';
    if ($sec < 60) return $sec . 's';
    $m = (int)floor($sec / 60);
    $s = $sec % 60;
    return "{$m}m {$s}s";
}
function short_url(string $url): string {
    $p = parse_url($url);
    if (!$p || empty($p['host'])) return h($url);
    $path = $p['path'] ?? '/';
    return h($p['host'] . $path);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TNP Admin — Analytics</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css?v=<?= @filemtime(__DIR__ . '/style.css') ?: time() ?>">
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <img class="brand-logo" src="/assets/tnp-logo.jpg" alt="TNP">
            <span>
                <span class="brand-name">TNP Admin</span>
                <br><span class="brand-tag">Dashboard</span>
            </span>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-label">Manage</div>
            <a class="sidebar-item" href="/admin">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                Offers
            </a>
            <a class="sidebar-item active" href="/analytics">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>
                Analytics
            </a>
            <a class="sidebar-item" href="/" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14L21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                View Public Page
            </a>
        </nav>
        <div class="sidebar-foot">
            <div class="sidebar-user">
                <span class="avatar">A</span>
                <span>Admin</span>
            </div>
            <a class="sidebar-item" href="/logout">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>Analytics</h1>
                <p class="subtitle">Visitor traffic to the public offers directory.</p>
            </div>
        </div>

        <!-- Filters -->
        <form class="analytics-filters" method="get">
            <div class="range-chips">
                <?php foreach ([
                    'today' => 'Last 24h',
                    '7d'    => '7 days',
                    '30d'   => '30 days',
                    '90d'   => '90 days',
                    'all'   => 'All time',
                ] as $k => $label): ?>
                    <button type="submit" name="range" value="<?= $k ?>" class="range-chip <?= $range === $k ? 'active' : '' ?>"><?= $label ?></button>
                <?php endforeach; ?>
            </div>
            <div class="filter-row">
                <select name="device" onchange="this.form.submit()">
                    <option value="">All devices</option>
                    <?php foreach ($devices as $d): ?>
                        <option value="<?= h($d) ?>" <?= $device === $d ? 'selected' : '' ?>><?= h($d) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="country" onchange="this.form.submit()">
                    <option value="">All countries</option>
                    <?php foreach ($countries as $c): if (!$c['country_code']) continue; ?>
                        <option value="<?= h($c['country_code']) ?>" <?= $country === $c['country_code'] ? 'selected' : '' ?>>
                            <?= h($c['name'] ?: strtoupper($c['country_code'])) ?> (<?= (int)$c['c'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="source" onchange="this.form.submit()">
                    <option value="">All sources</option>
                    <?php foreach ($sources as $src): ?>
                        <option value="<?= h($src) ?>" <?= $source === $src ? 'selected' : '' ?>><?= h($src) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($device || $country || $source): ?>
                    <a class="btn btn-secondary btn-small" href="/analytics?range=<?= h($range) ?>">Clear filters</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <p class="stat-label">Total Visits</p>
                <p class="stat-value"><?= number_format((int)($s['visits'] ?? 0)) ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Unique Sessions</p>
                <p class="stat-value"><?= number_format((int)($s['sessions'] ?? 0)) ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Unique IPs</p>
                <p class="stat-value"><?= number_format((int)($s['unique_ips'] ?? 0)) ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Avg. Scroll</p>
                <p class="stat-value"><?= $s['avg_scroll'] !== null ? ((int)$s['avg_scroll'] . '%') : '—' ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-label">Avg. Time on Page</p>
                <p class="stat-value"><?= fmt_duration((int)($s['avg_duration'] ?? 0)) ?></p>
            </div>
        </div>

        <!-- Table -->
        <div class="section-head" style="margin-top: 1.75rem;">
            <h2 class="section-title">Traffic Log <span class="count">(<?= number_format($total_rows) ?>)</span></h2>
        </div>

        <?php if (empty($visits)): ?>
            <div class="card">
                <div class="empty">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/></svg>
                    </div>
                    <p class="empty-title">No visits yet</p>
                    <p class="empty-text">Visits to the public directory will show up here.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="table-wrap">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Country</th>
                                <th>IP</th>
                                <th>Device</th>
                                <th>Source</th>
                                <th>Page</th>
                                <th>Scroll</th>
                                <th>Duration</th>
                                <th>Clicks</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($visits as $v):
                            $ts = strtotime((string)$v['visited_at']);
                            $scroll = $v['max_scroll'] !== null ? (int)$v['max_scroll'] : null;
                            $dur = $v['duration_sec'] !== null ? (int)$v['duration_sec'] : 0;
                            $clicks = json_decode($v['clicks_json'] ?? '[]', true) ?: [];
                        ?>
                            <tr>
                                <td class="ts">
                                    <span class="ts-rel"><?= human_time_ago($ts) ?></span>
                                    <span class="ts-abs"><?= h(date('M j, H:i', $ts)) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($v['country_code'])): ?>
                                        <span class="country-cell">
                                            <img src="https://flagcdn.com/w20/<?= h(strtolower($v['country_code'])) ?>.png" alt="" width="16" height="12">
                                            <?= h($v['country_name'] ?: strtoupper($v['country_code'])) ?><?php if ($v['city']): ?>
                                                <span class="city">· <?= h($v['city']) ?></span>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><code class="ip"><?= h($v['ip_address']) ?></code></td>
                                <td>
                                    <span class="device-badge device-<?= h(strtolower($v['device'] ?? 'unknown')) ?>">
                                        <?= h($v['device'] ?: 'Unknown') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($v['referrer'])): ?>
                                        <span class="src-pill" title="<?= h($v['referrer']) ?>"><?= h($v['referrer_source'] ?: 'Other') ?></span>
                                    <?php else: ?>
                                        <span class="src-pill src-direct">Direct</span>
                                    <?php endif; ?>
                                </td>
                                <td class="page-cell"><?= short_url((string)$v['page_url']) ?></td>
                                <td>
                                    <?php if ($scroll !== null): ?>
                                        <div class="scroll-bar" title="<?= $scroll ?>%">
                                            <div class="scroll-fill scroll-<?= $scroll >= 70 ? 'hot' : ($scroll >= 35 ? 'warm' : 'cool') ?>" style="width: <?= $scroll ?>%"></div>
                                            <span class="scroll-val"><?= $scroll ?>%</span>
                                        </div>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= fmt_duration($dur) ?></td>
                                <td>
                                    <?php if (empty($clicks)): ?>
                                        <span class="muted">—</span>
                                    <?php else: ?>
                                        <details class="click-details">
                                            <summary><?= count($clicks) ?> click<?= count($clicks) === 1 ? '' : 's' ?></summary>
                                            <ul>
                                            <?php foreach ($clicks as $c):
                                                $lbl = match ($c['type'] ?? '') {
                                                    'promote' => 'Promote',
                                                    'lander'  => 'Lander',
                                                    'link'    => 'Link',
                                                    default   => 'Click',
                                                };
                                                $meta = $c['offer'] ?? $c['title'] ?? $c['url'] ?? '';
                                            ?>
                                                <li><strong><?= h($lbl) ?>:</strong> <?= h($meta) ?></li>
                                            <?php endforeach; ?>
                                            </ul>
                                        </details>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1):
                $qs = function ($p) use ($range, $device, $country, $source) {
                    $q = http_build_query(array_filter(['range' => $range, 'device' => $device, 'country' => $country, 'source' => $source, 'p' => $p]));
                    return '?' . $q;
                };
            ?>
                <nav class="pagination">
                    <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $page > 1 ? $qs($page - 1) : '#' ?>">← Prev</a>
                    <span class="page-info">Page <?= $page ?> of <?= $total_pages ?></span>
                    <a class="page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>" href="<?= $page < $total_pages ? $qs($page + 1) : '#' ?>">Next →</a>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
<?php
function human_time_ago(int $ts): string {
    $diff = time() - $ts;
    if ($diff < 60)    return $diff . 's ago';
    if ($diff < 3600)  return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', $ts);
}
