<?php
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/analytics.php';
require __DIR__ . '/includes/countries.php';

$offers = [];
$next_sr_val = 1;
try {
    $pdo = get_pdo();
    $offers = $pdo->query('SELECT * FROM offers ORDER BY sr ASC')->fetchAll();
    $next_sr_val = next_sr($pdo);
} catch (Throwable $e) {
    // Show empty; errors handled inline
}

$total_offers = count($offers);
$unique_categories = count(array_unique(array_filter(array_column($offers, 'category'))));
$unique_platforms = count(array_unique(array_filter(array_column($offers, 'platform'))));
$with_images = 0;
foreach ($offers as $o) {
    if (!empty($o['image_url'])) $with_images++;
}

// Shaver analytics: fetch active domain suggestions
$shaver = shaver_fetch_domains();
$existing_names = [];
$existing_shaver_ids = [];
foreach ($offers as $o) {
    $existing_names[strtolower(trim($o['offer_name']))] = true;
    if (!empty($o['shaver_domain_id'])) {
        $existing_shaver_ids[(int)$o['shaver_domain_id']] = true;
    }
}

// Dismissed Shaver domains (user ne × pe click kiya)
$dismissed_ids = [];
try {
    $rows = $pdo->query('SELECT shaver_domain_id FROM dismissed_shaver_domains')->fetchAll();
    foreach ($rows as $r) $dismissed_ids[(int)$r['shaver_domain_id']] = true;
} catch (Throwable $e) { /* table may not exist yet, treat as empty */ }

// Platform defaults — "learn" most-common values per platform
$platform_defaults = [];
$counts = [];
foreach ($offers as $o) {
    $p = trim($o['platform'] ?? '');
    if ($p === '') continue;
    foreach (['revshare', 'cpa', 'allowed_geos', 'restriction', 'category'] as $field) {
        $val = trim($o[$field] ?? '');
        if ($val === '') continue;
        $counts[$p][$field][$val] = ($counts[$p][$field][$val] ?? 0) + 1;
    }
}
foreach ($counts as $p => $fields) {
    foreach ($fields as $field => $values) {
        arsort($values);
        $top_val = array_key_first($values);
        if ($top_val !== null) $platform_defaults[$p][$field] = $top_val;
    }
}

// Traffic tip defaults — per-platform, most-common value per tip label
$platform_tip_defaults = [];
$tip_counts = [];
foreach ($offers as $o) {
    $p = trim($o['platform'] ?? '');
    if ($p === '') continue;
    $tips = json_decode($o['traffic_tips'] ?? '[]', true) ?: [];
    foreach ($tips as $t) {
        if (!is_array($t)) continue;
        $lbl = trim((string)($t['label'] ?? ''));
        $val = trim((string)($t['value'] ?? ''));
        if ($lbl === '' || $val === '') continue;
        $tip_counts[$p][$lbl][$val] = ($tip_counts[$p][$lbl][$val] ?? 0) + 1;
    }
}
foreach ($tip_counts as $p => $labels) {
    foreach ($labels as $lbl => $values) {
        arsort($values);
        $top_val = array_key_first($values);
        if ($top_val !== null) {
            $platform_tip_defaults[$p][] = ['label' => $lbl, 'value' => $top_val];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TNP Admin — Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css?v=<?= @filemtime(__DIR__ . '/style.css') ?: time() ?>">
</head>
<body>
<div class="admin-shell">
    <!-- Sidebar -->
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
            <a class="sidebar-item active" href="/admin">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                Offers
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

    <!-- Main -->
    <main class="admin-main" data-next-sr="<?= h((string)$next_sr_val) ?>">
        <div class="admin-topbar">
            <div>
                <h1>Offers</h1>
                <p class="subtitle">Manage your affiliate offer directory</p>
            </div>
            <button class="btn btn-primary" onclick="openForm()">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Offer
            </button>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <p class="stat-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3z"/><path d="M9 9h6v6H9z"/></svg>
                    Total Offers
                </p>
                <p class="stat-value"><?= $total_offers ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><circle cx="7" cy="7" r="1"/></svg>
                    Categories
                </p>
                <p class="stat-value"><?= $unique_categories ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Platforms
                </p>
                <p class="stat-value"><?= $unique_platforms ?></p>
            </div>
            <div class="stat-card">
                <p class="stat-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                    With Image
                </p>
                <p class="stat-value"><?= $with_images ?></p>
            </div>
        </div>

        <!-- Suggestions from Shaver -->
        <?php if ($shaver['ok'] && !empty($shaver['domains'])): ?>
            <?php
                $new_suggestions = [];
                foreach ($shaver['domains'] as $d) {
                    $label = strtolower(trim($d['label'] ?? ''));
                    $dom_id = (int)($d['id'] ?? 0);
                    if ($label === '') continue;
                    if (isset($existing_shaver_ids[$dom_id])) continue; // already imported
                    if (isset($existing_names[$label])) continue;       // name collides
                    if (isset($dismissed_ids[$dom_id])) continue;       // user dismissed
                    $new_suggestions[] = $d;
                }
            ?>
            <?php if (!empty($new_suggestions)): ?>
            <section class="magic-section">
                <div class="magic-header">
                    <h2 class="section-title">
                        <span class="magic-emoji">✨</span>
                        Suggestions from Shaver
                        <span class="count">(<?= count($new_suggestions) ?> new)</span>
                    </h2>
                    <p class="subtitle">Active domains not yet in this directory — click any to pre-fill the form.</p>
                </div>
                <div class="suggest-grid">
                    <?php foreach ($new_suggestions as $d): ?>
                        <div class="suggest-card-wrap" data-shaver-id="<?= h((string)$d['id']) ?>">
                            <button type="button" class="suggest-dismiss" title="Dismiss forever" onclick="dismissSuggestion(event, this)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                            <button type="button" class="suggest-card"
                                    data-suggest='<?= h(json_encode(shaver_domain_to_offer($d), JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>
                                <div class="suggest-card-head">
                                    <span class="suggest-label"><?= h($d['label']) ?></span>
                                    <span class="badge badge-slate"><?= h(normalize_platform($d['platform'])) ?></span>
                                </div>
                                <span class="suggest-url"><?= h($d['domain_url']) ?></span>
                                <span class="suggest-cta">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    Add to directory
                                </span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        <?php elseif (!$shaver['ok'] && ($shaver['error'] ?? '') !== 'not_configured'): ?>
            <div class="section-head" style="margin-top: 2rem;">
                <h2 class="section-title">Suggestions from Shaver</h2>
            </div>
            <div class="error-block">
                Could not load Shaver suggestions: <?= h((string)($shaver['error'] ?? 'unknown')) ?>.
            </div>
        <?php endif; ?>

        <!-- Table -->
        <div class="section-head" style="margin-top: 2rem;">
            <h2 class="section-title">All Offers <span class="count">(<?= $total_offers ?>)</span></h2>
            <div class="search-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="search" class="search-input" id="adminSearch" placeholder="Search offers...">
            </div>
        </div>

        <?php if (empty($offers)): ?>
            <div class="card">
                <div class="empty">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7h-7L10 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/></svg>
                    </div>
                    <p class="empty-title">No offers yet</p>
                    <p class="empty-text">Click "Add Offer" above to add your first entry.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="table-wrap">
                    <table class="offer-table">
                        <thead>
                            <tr>
                                <th>Sr</th>
                                <th class="product-head">Product</th>
                                <th>Offer</th>
                                <th>Top Landers</th>
                                <th>Links</th>
                                <th>Commission</th>
                                <th>GEOs</th>
                                <th>Traffic Tips</th>
                                <th class="center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="offersBody">
                        <?php foreach ($offers as $o):
                            $search_text = strtolower(implode(' ', [
                                $o['offer_name'], $o['offer_id'], $o['platform'],
                                $o['category'], $o['allowed_geos']
                            ]));
                            $stored_landers = json_decode($o['top_landers'] ?? '[]', true) ?: [];
                            $restriction_val = $o['restriction'] ?: 'No';
                            $soon = !empty($o['coming_soon']);
                            $blank = $soon ? coming_soon_chip() : '<span class="muted">—</span>';
                        ?>
                            <tr data-id="<?= h((string)$o['id']) ?>" data-search="<?= h($search_text) ?>" data-offer='<?= h(json_encode($o, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>' draggable="true">
                                <td class="sr">
                                    <span class="drag-handle" title="Drag to reorder">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="5" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="9" cy="19" r="1.6"/><circle cx="15" cy="5" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="15" cy="19" r="1.6"/></svg>
                                    </span>
                                    <span class="sr-num"><?= h((string)$o['sr']) ?></span>
                                </td>
                                <td class="product-cell">
                                    <?php if (!empty($o['image_url'])): ?>
                                        <img class="thumb<?= is_transparent_image($o['image_url']) ? ' thumb-clean' : '' ?>" src="<?= h($o['image_url']) ?>" alt="">
                                    <?php elseif ($soon): ?>
                                        <span class="thumb-empty thumb-soon">Soon</span>
                                    <?php else: ?>
                                        <span class="thumb-empty">—</span>
                                    <?php endif; ?>
                                    <?php if (!empty($o['platform'])): ?>
                                        <span class="<?= platform_class($o['platform']) ?>"><?= h($o['platform']) ?></span>
                                    <?php elseif ($soon): ?>
                                        <?= $blank ?>
                                    <?php endif; ?>
                                </td>
                                <td class="offer-cell">
                                    <?php if (!empty($o['offer_name'])): ?>
                                        <span class="offer-name"><?= h($o['offer_name']) ?></span>
                                    <?php elseif ($soon): ?>
                                        <span class="offer-name muted">Coming Soon</span>
                                    <?php endif; ?>
                                    <?php if (!empty($o['offer_id'])): ?>
                                        <?php $id_label = (strcasecmp($o['platform'] ?? '', 'ClickBank') === 0) ? 'Nickname' : 'Offer ID'; ?>
                                        <span class="offer-id"><?= $id_label ?>: <?= h($o['offer_id']) ?></span>
                                    <?php elseif ($soon): ?>
                                        <span class="offer-id"><?= $blank ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($o['category'])): ?>
                                        <span class="<?= category_class($o['category']) ?>"><?= h($o['category']) ?></span>
                                    <?php elseif ($soon): ?>
                                        <?= $blank ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (empty($stored_landers)): ?>
                                        <?= $blank ?>
                                    <?php else: ?>
                                        <div class="landers">
                                        <?php foreach ($stored_landers as $l):
                                            $lurl    = $l['url'] ?? '#';
                                            $info    = lander_info_from_url($lurl);
                                            $hasMan  = !empty($l['advice']);
                                            if ($hasMan) {
                                                $label  = $l['label'] ?? $info['label'];
                                                $advice = $l['advice'];
                                                $derived_type = advice_type_for($advice);
                                                $type = $derived_type !== 'custom' ? $derived_type : ($l['type'] ?? 'custom');
                                                $desc   = advice_description($advice);
                                                $tip    = $desc ? $advice . ' — ' . $desc : '';
                                            } else {
                                                $label  = $info['type'] !== 'other' ? $info['label'] : ($l['label'] ?? $info['label']);
                                                $type   = $info['type'];
                                                $advice = $info['advice'];
                                                $tip    = $info['description'] ? $info['label'] . ' — ' . $info['description'] : '';
                                            }
                                        ?>
                                            <a href="<?= h($lurl) ?>" target="_blank" rel="noopener noreferrer"
                                               class="lander-link"
                                               <?= $tip ? 'data-tip="' . h($tip) . '" aria-label="' . h($tip) . '"' : '' ?>>
                                                <?php if ($advice): ?>
                                                    <span class="advice-chip advice-<?= h($type) ?>"><?= h($advice) ?></span>
                                                <?php else: ?>
                                                    <span class="advice-slot"></span>
                                                <?php endif; ?>
                                                <span class="lander-name">
                                                    <?= h($label) ?>
                                                    <svg class="lander-arrow" xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17l10-10"/><path d="M7 7h10v10"/></svg>
                                                </span>
                                            </a>
                                        <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $offer_links = json_decode($o['links'] ?? '[]', true) ?: [];
                                        if (empty($offer_links) && !empty($o['affiliate_page_url'])) {
                                            $offer_links = [['title' => 'Affiliate Page', 'url' => $o['affiliate_page_url']]];
                                        }
                                    ?>
                                    <?php if (empty($offer_links)): ?>
                                        <?= $blank ?>
                                    <?php else: ?>
                                        <div class="offer-links">
                                        <?php foreach ($offer_links as $ln):
                                            if (empty($ln['url'])) continue;
                                        ?>
                                            <a class="link" href="<?= h($ln['url']) ?>" target="_blank" rel="noopener noreferrer">
                                                <?= h($ln['title'] ?? 'Link') ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17l10-10"/><path d="M7 7h10v10"/></svg>
                                            </a>
                                        <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="commission-cell">
                                    <?php
                                        $hasRev = !empty($o['revshare']);
                                        $hasCpa = !empty($o['cpa']);
                                        $cpaManual = !empty($o['cpa_manual']);
                                        $cpaTip = 'Manually activated CPA — Ask the admin to unlock it, or complete 10 sales on RevShare and it will be activated for you automatically.';
                                    ?>
                                    <?php if (!$hasRev && !$hasCpa): ?>
                                        <?= $blank ?>
                                    <?php else: ?>
                                        <?php if ($hasRev): ?>
                                            <div class="commission-rev"><strong><?= h($o['revshare']) ?></strong> Revshare</div>
                                        <?php endif; ?>
                                        <?php if ($hasRev && $hasCpa): ?>
                                            <div class="commission-or">OR</div>
                                        <?php endif; ?>
                                        <?php if ($hasCpa): ?>
                                            <div class="commission-cpa">
                                                <strong><?= h($o['cpa']) ?></strong> Fixed CPA
                                                <?php if ($cpaManual): ?>
                                                    <span class="cpa-alert" data-tip="<?= h($cpaTip) ?>" aria-label="<?= h($cpaTip) ?>">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="muted">
                                    <?php if ($o['allowed_geos'] === 'Tier-1 (39 Countries)'): ?>
                                        <button type="button" class="geo-link" onclick="showCountries()">
                                            Tier-1 (39 Countries)
                                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                        </button>
                                    <?php elseif (!empty($o['allowed_geos'])): ?>
                                        <?= h($o['allowed_geos']) ?>
                                    <?php else: ?>
                                        <?= $blank ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $tips = json_decode($o['traffic_tips'] ?? '[]', true) ?: [];
                                    ?>
                                    <?php if (empty($tips)): ?>
                                        <?= $blank ?>
                                    <?php else: ?>
                                        <ul class="tip-list">
                                        <?php foreach ($tips as $t):
                                            $lbl = is_array($t) ? ($t['label'] ?? '') : '';
                                            $val = is_array($t) ? ($t['value'] ?? '') : (is_string($t) ? $t : '');
                                            if ($val === '') continue;
                                        ?>
                                            <li><?php if ($lbl): ?><strong><?= h($lbl) ?>:</strong> <?php endif; ?><?= h($val) ?></li>
                                        <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </td>
                                <td class="center">
                                    <button class="btn-small btn-edit" onclick="editOffer(this)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                                        Edit
                                    </button>
                                    <button class="btn-small btn-delete" onclick="deleteOffer(this)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14H7L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Add/Edit Modal -->
<div id="modal" class="modal hidden">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Offer</h3>
            <button class="modal-close" onclick="closeForm()">&times;</button>
        </div>
        <form id="offerForm" class="modal-body" onsubmit="submitForm(event)">
            <input type="hidden" name="id" id="f_id">

            <!-- Basics -->
            <section class="form-section section-basics">
                <h4 class="section-head-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Basics
                </h4>
                <label class="coming-soon-toggle" id="comingSoonToggle">
                    <input type="checkbox" id="f_coming_soon" onchange="toggleComingSoon()">
                    <span class="cs-dot"></span>
                    <span class="cs-label">
                        <strong>Coming Soon</strong>
                        <em>All required fields become optional. Any blank field will show "Coming Soon" on the public page.</em>
                    </span>
                </label>
                <div class="grid">
                    <div><label>Sr # <span class="required">*</span></label><input type="number" name="sr" id="f_sr" required></div>
                    <div>
                        <label>Platform <span class="required">*</span> <span class="field-hint" id="hint_platform"></span></label>
                        <select name="platform" id="f_platform" required>
                            <option>BuyGoods</option><option>ClickBank</option><option>Digistore24</option><option>MaxWeb</option><option>Other</option>
                        </select>
                    </div>
                    <div class="full"><label>Offer Name <span class="required">*</span></label><input type="text" name="offer_name" id="f_offer_name" required></div>
                    <div>
                        <label>Offer ID / Nickname <span class="field-hint" id="hint_offer_id"></span></label>
                        <input type="text" name="offer_id" id="f_offer_id">
                    </div>
                    <div class="full cb-only" id="cb_url_field" style="display:none;">
                        <label>ClickBank Redirect URL <span class="required">*</span></label>
                        <input type="url" name="clickbank_redirect_url" id="f_cb_url" placeholder="https://www.clickbank.com/mkplSearchResult?...">
                        <p class="field-help">Required for ClickBank offers. The "Promote Now" button will redirect affiliates here.</p>
                    </div>
                    <div>
                        <label>Category <span class="field-hint" id="hint_category"></span></label>
                        <select name="category" id="f_category">
                            <option>Weight Loss</option><option>Male Enhancement</option>
                            <option>Blood Sugar</option><option>Brain Health</option>
                            <option>Joint Pain</option><option>Other</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Product Image -->
            <section class="form-section section-image">
                <h4 class="section-head-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                    Product Image
                </h4>
                <div class="image-tabs">
                    <button type="button" class="tab active" data-tab="url" onclick="switchImageTab('url')">Paste URL</button>
                    <button type="button" class="tab" data-tab="upload" onclick="switchImageTab('upload')">Upload File</button>
                </div>
                <div id="imageUrlPane" class="tab-pane">
                    <input type="text" id="f_image_url" placeholder="https://example.com/image.jpg or /uploads/xxx.png" oninput="previewImage(this.value)">
                </div>
                <div id="imageUploadPane" class="tab-pane hidden">
                    <input type="file" id="f_image_file" accept="image/png,image/jpeg,image/webp,image/gif" onchange="uploadImage(this)">
                    <p class="hint" id="uploadStatus"></p>
                </div>
                <div id="imagePreview" class="image-preview hidden">
                    <img id="previewImg" src="" alt="">
                    <button type="button" class="remove-img" onclick="clearImage()" title="Remove image">&times;</button>
                </div>
            </section>

            <!-- Commission & GEOs -->
            <section class="form-section section-commission">
                <h4 class="section-head-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Commission & GEOs
                </h4>
                <div class="grid">
                    <div>
                        <label>RevShare <span class="field-hint" id="hint_revshare"></span></label>
                        <input type="text" name="revshare" id="f_revshare" placeholder="e.g. 75%">
                    </div>
                    <div>
                        <label>CPA <span class="field-hint" id="hint_cpa"></span></label>
                        <input type="text" name="cpa" id="f_cpa" placeholder="e.g. $170">
                        <label class="checkbox-inline">
                            <input type="checkbox" id="f_cpa_manual">
                            <span>CPA is manually activated (shows alert to affiliates)</span>
                        </label>
                    </div>
                    <div>
                        <label>Allowed GEOs <span class="field-hint" id="hint_allowed_geos"></span></label>
                        <select name="allowed_geos" id="f_allowed_geos">
                            <?php foreach (GEO_OPTIONS as $opt): ?>
                                <option value="<?= h($opt) ?>"><?= h($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Restriction <span class="field-hint" id="hint_restriction"></span></label>
                        <select name="restriction" id="f_restriction"><option>No</option><option>Yes</option></select>
                    </div>
                </div>
            </section>

            <!-- Links -->
            <section class="form-section section-links">
                <h4 class="section-head-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    Links
                    <span class="field-hint" id="hint_links"></span>
                </h4>
                <div class="lander-row">
                    <select id="link_title">
                        <option value="Affiliate Page">Affiliate Page</option>
                        <option value="Creatives">Creatives</option>
                        <option value="Traffic Tips">Traffic Tips</option>
                        <option value="Ad Copy Swipes">Ad Copy Swipes</option>
                    </select>
                    <input type="text" id="link_url" placeholder="https://...">
                    <button type="button" class="btn btn-primary" onclick="addLink()">+ Add</button>
                </div>
                <div id="linksList" class="landers-list"></div>
            </section>

            <!-- Traffic Tips -->
            <section class="form-section section-tips">
                <h4 class="section-head-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.663 17h4.673M12 3v1m6.364 1.636-.707.707M21 12h-1M4 12H3m3.343-5.657-.707-.707m2.828 9.9a5 5 0 1 1 7.072 0l-.548.547A3.374 3.374 0 0 0 14 18.469V19a2 2 0 1 1-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    Traffic Tips
                    <span class="field-hint" id="hint_traffic_tips"></span>
                </h4>
                <div class="lander-row">
                    <select id="tip_label">
                        <option value="Restriction">Restriction</option>
                        <option value="Age">Age</option>
                        <option value="EPC">EPC</option>
                        <option value="AOV">AOV</option>
                    </select>
                    <input type="text" id="tip_input" placeholder="e.g. 18+, $2.50, US only">
                    <button type="button" class="btn btn-primary" onclick="addTip()">+ Add</button>
                </div>
                <div id="tipsList" class="landers-list"></div>
            </section>

            <!-- Top Landers -->
            <section class="form-section section-landers">
                <h4 class="section-head-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Top Landers
                    <span class="field-hint" id="hint_landers"></span>
                </h4>
                <div class="lander-row">
                    <input type="text" id="lander_label" placeholder="Label (e.g. Lander 1)">
                    <input type="text" id="lander_url" placeholder="https://...">
                    <select id="lander_advice" title="Capsule type">
                        <option value="">— Capsule (optional) —</option>
                        <option value="prelander">prelander</option>
                        <option value="direct-link">direct-link</option>
                        <option value="VSL">VSL</option>
                    </select>
                    <button type="button" class="btn btn-primary" onclick="addLander()">+ Add</button>
                </div>
                <div id="landersList" class="landers-list"></div>
            </section>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeForm()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Offer</button>
            </div>
        </form>
    </div>
</div>

<!-- Tier-1 countries modal -->
<div id="countriesModal" class="modal hidden" onclick="if(event.target===this) hideCountries()">
    <div class="modal-card countries-modal">
        <div class="modal-header">
            <h3>Tier-1 Countries <span class="modal-count">39 total</span></h3>
            <button class="modal-close" onclick="hideCountries()">&times;</button>
        </div>
        <div class="modal-body">
            <?php foreach (TIER1_COUNTRIES as $region => $countries_in_region): ?>
                <div class="country-region">
                    <h4><?= h($region) ?> <span class="region-count">(<?= count($countries_in_region) ?>)</span></h4>
                    <div class="country-grid">
                        <?php foreach ($countries_in_region as $c): ?>
                            <div class="country-item">
                                <img class="country-flag"
                                     src="https://flagcdn.com/w40/<?= h($c['code']) ?>.png"
                                     srcset="https://flagcdn.com/w80/<?= h($c['code']) ?>.png 2x"
                                     width="24" height="18" alt="<?= h($c['name']) ?>" loading="lazy">
                                <span class="country-name"><?= h($c['name']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    window.PLATFORM_DEFAULTS = <?= json_encode($platform_defaults, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.PLATFORM_TIP_DEFAULTS = <?= json_encode($platform_tip_defaults, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    function showCountries() {
        document.getElementById('countriesModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function hideCountries() {
        document.getElementById('countriesModal').classList.add('hidden');
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') hideCountries();
    });
</script>
<script src="/app.js?v=<?= @filemtime(__DIR__ . '/app.js') ?: time() ?>"></script>
<script>
    // Live search filter for admin table
    const search = document.getElementById('adminSearch');
    if (search) {
        search.addEventListener('input', e => {
            const q = e.target.value.toLowerCase().trim();
            document.querySelectorAll('#offersBody tr').forEach(row => {
                const text = row.dataset.search || '';
                row.style.display = !q || text.includes(q) ? '' : 'none';
            });
        });
    }

    // Shaver suggestion click → pre-fill Add Offer modal
    document.querySelectorAll('.suggest-card').forEach(card => {
        card.addEventListener('click', () => {
            try {
                const offer = JSON.parse(card.dataset.suggest);
                openForm(offer);
            } catch (e) {
                console.error('Failed to parse suggestion', e);
            }
        });
    });

    // Drag-to-reorder offers: the admin picks a row order here, and the
    // public viewer reads the table ORDER BY sr ASC. So on drop we POST
    // the new id sequence to /api.php?action=reorder which renumbers
    // offers.sr sequentially (1..N).
    (function initRowDrag() {
        const tbody = document.getElementById('offersBody');
        if (!tbody) return;
        let dragged = null;

        tbody.addEventListener('dragstart', e => {
            const tr = e.target.closest('tr');
            if (!tr) return;
            dragged = tr;
            tr.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        tbody.addEventListener('dragend', () => {
            if (dragged) dragged.classList.remove('dragging');
            tbody.querySelectorAll('.drag-over').forEach(r => r.classList.remove('drag-over'));
            dragged = null;
        });
        tbody.addEventListener('dragover', e => {
            e.preventDefault();
            const tr = e.target.closest('tr');
            if (!tr || tr === dragged) return;
            tbody.querySelectorAll('.drag-over').forEach(r => r.classList.remove('drag-over'));
            tr.classList.add('drag-over');
            const rect = tr.getBoundingClientRect();
            const before = (e.clientY - rect.top) < rect.height / 2;
            tbody.insertBefore(dragged, before ? tr : tr.nextSibling);
        });
        tbody.addEventListener('drop', async e => {
            e.preventDefault();
            tbody.querySelectorAll('.drag-over').forEach(r => r.classList.remove('drag-over'));
            const ids = Array.from(tbody.querySelectorAll('tr')).map(r => Number(r.dataset.id)).filter(Boolean);
            // Renumber Sr visually right away
            Array.from(tbody.querySelectorAll('tr')).forEach((r, i) => {
                const n = r.querySelector('.sr-num');
                if (n) n.textContent = String(i + 1);
            });
            try {
                const res = await fetch('/api.php?action=reorder', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids }),
                });
                const result = await res.json();
                if (!result.ok) alert('Reorder failed: ' + (result.error || 'Unknown'));
            } catch (err) {
                alert('Reorder request failed: ' + err.message);
            }
        });
    })();
</script>
</body>
</html>
