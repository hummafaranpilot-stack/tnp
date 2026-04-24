<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/analytics.php';
require __DIR__ . '/includes/countries.php';

$offers = [];
$load_error = '';
try {
    $pdo = get_pdo();
    $offers = $pdo->query('SELECT * FROM offers ORDER BY sr ASC')->fetchAll();
} catch (Throwable $e) {
    $load_error = 'Unable to load offers right now. Please try again later.';
}

// Extract unique filter values
$categories = [];
$platforms  = [];
$geos       = [];
foreach ($offers as $o) {
    if (!empty($o['category'])) $categories[$o['category']] = true;
    if (!empty($o['platform'])) $platforms[$o['platform']]  = true;
    if (!empty($o['allowed_geos'])) $geos[$o['allowed_geos']] = true;
}
$categories = array_keys($categories);
$platforms  = array_keys($platforms);
$geos       = array_keys($geos);
sort($categories);
sort($platforms);
sort($geos);

// Stats
$total_offers = count($offers);
$unique_categories = count($categories);
$unique_platforms = count($platforms);
$tier1_count = 0;
foreach ($offers as $o) {
    if (stripos($o['allowed_geos'] ?? '', 'tier-1') !== false) $tier1_count++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrustedNutraProduct — All Offers Directory</title>
    <meta name="description" content="Affiliate offers directory for TrustedNutraProduct partners.">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css?v=<?= @filemtime(__DIR__ . '/style.css') ?: time() ?>">
</head>
<body>
<button class="sidebar-toggle-btn" onclick="toggleSidebar()" aria-label="Toggle filters sidebar" title="Toggle filters">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
</button>
<div class="admin-shell" id="adminShell">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <img class="brand-logo" src="/assets/tnp-logo.jpg" alt="TNP">
            <span>
                <span class="brand-name">TrustedNutraProduct</span>
                <br><span class="brand-tag">Affiliate Directory</span>
            </span>
        </div>

        <div class="sidebar-scroll">
            <div class="filter-group">
                <div class="filter-group-head">
                    <span class="sidebar-label">Category</span>
                </div>
                <div class="filter-chips">
                    <?php foreach ($categories as $c): ?>
                        <button type="button" class="filter-chip" data-filter="category" data-value="<?= h($c) ?>"><?= h($c) ?></button>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?>
                        <span class="filter-empty">None yet</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="filter-group">
                <div class="filter-group-head">
                    <span class="sidebar-label">Platform</span>
                </div>
                <div class="filter-chips">
                    <?php foreach ($platforms as $p): ?>
                        <button type="button" class="filter-chip" data-filter="platform" data-value="<?= h($p) ?>"><?= h($p) ?></button>
                    <?php endforeach; ?>
                    <?php if (empty($platforms)): ?>
                        <span class="filter-empty">None yet</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="filter-group">
                <div class="filter-group-head">
                    <span class="sidebar-label">GEOs</span>
                </div>
                <div class="filter-chips">
                    <?php foreach ($geos as $g): ?>
                        <button type="button" class="filter-chip" data-filter="geo" data-value="<?= h($g) ?>"><?= h($g) ?></button>
                    <?php endforeach; ?>
                    <?php if (empty($geos)): ?>
                        <span class="filter-empty">None yet</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="filter-group">
                <div class="filter-group-head">
                    <span class="sidebar-label">Restriction</span>
                </div>
                <div class="filter-chips">
                    <button type="button" class="filter-chip" data-filter="restriction" data-value="No">No Restriction</button>
                    <button type="button" class="filter-chip" data-filter="restriction" data-value="Yes">Restricted</button>
                </div>
            </div>

            <button type="button" class="clear-filters hidden" id="clearFilters" onclick="clearAllFilters()">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Clear all filters
            </button>
        </div>

        <div class="sidebar-foot">
            <a class="sidebar-item" href="/admin">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Admin Login
            </a>
        </div>
    </aside>

    <!-- Main -->
    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>All Offers Directory</h1>
                <p class="subtitle">Browse active affiliate offers from TrustedNutraProduct</p>
            </div>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    Tier-1 GEOs
                </p>
                <p class="stat-value"><?= $tier1_count ?></p>
            </div>
        </div>

        <div class="section-head" style="margin-top: 2rem;">
            <h2 class="section-title">Offers <span class="count" id="visibleCount">(<?= $total_offers ?>)</span></h2>
            <div class="search-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="search" class="search-input" id="offerSearch" placeholder="Search offers, categories, platforms...">
            </div>
        </div>

        <?php if ($load_error): ?>
            <div class="error-block"><?= h($load_error) ?></div>
        <?php elseif (empty($offers)): ?>
            <div class="card">
                <div class="empty">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7h-7L10 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/></svg>
                    </div>
                    <p class="empty-title">No offers available yet</p>
                    <p class="empty-text">Check back soon — new offers are added regularly.</p>
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
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="offersBody">
                        <?php foreach ($offers as $o):
                            $landers = json_decode($o['top_landers'] ?? '[]', true) ?: [];
                            $search_text = strtolower(implode(' ', [
                                $o['offer_name'], $o['offer_id'], $o['platform'],
                                $o['category'], $o['allowed_geos']
                            ]));
                            $restriction_val = $o['restriction'] ?: 'No';
                        ?>
                            <tr data-search="<?= h($search_text) ?>"
                                data-category="<?= h($o['category']) ?>"
                                data-platform="<?= h($o['platform']) ?>"
                                data-geo="<?= h($o['allowed_geos']) ?>"
                                data-restriction="<?= h($restriction_val) ?>">
                                <td class="sr"><?= h((string)$o['sr']) ?></td>
                                <td class="product-cell">
                                    <?php if (!empty($o['image_url'])): ?>
                                        <img class="thumb<?= is_transparent_image($o['image_url']) ? ' thumb-clean' : '' ?>" src="<?= h($o['image_url']) ?>" alt="<?= h($o['offer_name']) ?>">
                                    <?php else: ?>
                                        <span class="thumb-empty">—</span>
                                    <?php endif; ?>
                                    <?php if (!empty($o['platform'])): ?>
                                        <span class="<?= platform_class($o['platform']) ?>"><?= h($o['platform']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="offer-cell">
                                    <span class="offer-name"><?= h($o['offer_name']) ?></span>
                                    <?php if (!empty($o['offer_id'])): ?>
                                        <span class="offer-id">Offer ID: <?= h($o['offer_id']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($o['category'])): ?>
                                        <span class="<?= category_class($o['category']) ?>"><?= h($o['category']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (empty($landers)): ?>
                                        <span class="muted">—</span>
                                    <?php else: ?>
                                        <div class="landers">
                                        <?php foreach ($landers as $l):
                                            $lurl    = $l['url'] ?? '#';
                                            $info    = lander_info_from_url($lurl);
                                            $hasMan  = !empty($l['advice']);
                                            if ($hasMan) {
                                                $label  = $l['label'] ?? $info['label'];
                                                $type   = $l['type'] ?? 'custom';
                                                $advice = $l['advice'];
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
                                        <span class="muted">—</span>
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
                                    ?>
                                    <?php if (!$hasRev && !$hasCpa): ?>
                                        <span class="muted">—</span>
                                    <?php else: ?>
                                        <?php if ($hasRev): ?>
                                            <div class="commission-rev"><strong><?= h($o['revshare']) ?></strong> Revshare</div>
                                        <?php endif; ?>
                                        <?php if ($hasRev && $hasCpa): ?>
                                            <div class="commission-or">OR</div>
                                        <?php endif; ?>
                                        <?php if ($hasCpa): ?>
                                            <div class="commission-cpa"><strong><?= h($o['cpa']) ?></strong> Fixed CPA</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="muted">
                                    <?php if ($o['allowed_geos'] === 'Tier-1 (39 Countries)'): ?>
                                        <button type="button" class="geo-link" onclick="showCountries()">
                                            Tier-1 (39 Countries)
                                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                        </button>
                                    <?php else: ?>
                                        <?= h($o['allowed_geos']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $tips = json_decode($o['traffic_tips'] ?? '[]', true) ?: [];
                                    ?>
                                    <?php if (empty($tips)): ?>
                                        <span class="muted">—</span>
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
                                <td>
                                    <?php
                                        $promote_url = '';
                                        foreach ($offer_links as $ln) {
                                            if (($ln['title'] ?? '') === 'Affiliate Page' && !empty($ln['url'])) {
                                                $promote_url = $ln['url'];
                                                break;
                                            }
                                        }
                                        if (!$promote_url && !empty($offer_links[0]['url'])) {
                                            $promote_url = $offer_links[0]['url'];
                                        }
                                        if (!$promote_url && !empty($o['affiliate_page_url'])) {
                                            $promote_url = $o['affiliate_page_url'];
                                        }
                                    ?>
                                    <?php if ($promote_url): ?>
                                        <a class="btn-promote" href="<?= h($promote_url) ?>" target="_blank" rel="noopener noreferrer">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m13 2-2 7h9l-11 13 2-9H2z"/></svg>
                                            Promote Now
                                        </a>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="empty hidden" id="noMatches">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <p class="empty-title">No offers match your filters</p>
                <p class="empty-text">Try removing some filters or changing your search.</p>
            </div>
        <?php endif; ?>

        <p class="footer-note">&copy; <?= date('Y') ?> TrustedNutraProduct · All offers are independently verified.</p>
    </main>
</div>

<!-- Floating contact buttons -->
<div class="contact-fab">
    <a class="fab-btn fab-email" href="mailto:contact@trustednutraproduct.com" aria-label="Email us" data-tip="Email contact@trustednutraproduct.com">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
    </a>
    <a class="fab-btn fab-telegram" href="https://t.me/TrustedNutraProduct" target="_blank" rel="noopener noreferrer" aria-label="Telegram" data-tip="Message us on Telegram @TrustedNutraProduct">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
    </a>
</div>

<!-- Tier-1 countries modal -->
<div id="countriesModal" class="modal hidden" onclick="if(event.target===this) hideCountries()">
    <div class="modal-card countries-modal">
        <div class="modal-header">
            <h3>Tier-1 Countries <span class="modal-count">39 total</span></h3>
            <button class="modal-close" onclick="hideCountries()">&times;</button>
        </div>
        <div class="modal-body">
            <?php foreach (TIER1_COUNTRIES as $region => $countries): ?>
                <div class="country-region">
                    <h4><?= h($region) ?> <span class="region-count">(<?= count($countries) ?>)</span></h4>
                    <div class="country-grid">
                        <?php foreach ($countries as $c): ?>
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
    // Sidebar collapse toggle
    function toggleSidebar() {
        const shell = document.getElementById('adminShell');
        const collapsed = shell.classList.toggle('sidebar-collapsed');
        try { localStorage.setItem('tnp_sidebar_collapsed', collapsed ? '1' : '0'); } catch (_) {}
    }
    (function initSidebar() {
        try {
            if (localStorage.getItem('tnp_sidebar_collapsed') === '1') {
                document.getElementById('adminShell').classList.add('sidebar-collapsed');
            }
        } catch (_) {}
    })();

    // Countries modal controls
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

    // Custom hover tooltip for data-tip elements
    (function() {
        const tip = document.createElement('div');
        tip.className = 'custom-tooltip';
        document.body.appendChild(tip);
        function place(el) {
            const text = el.getAttribute('data-tip');
            if (!text) return;
            tip.innerHTML = '';
            const [heading, ...rest] = text.split(' — ');
            if (rest.length) {
                tip.innerHTML = '<strong>' + escapeHtml(heading) + '</strong><br>' + escapeHtml(rest.join(' — '));
            } else {
                tip.textContent = text;
            }
            const r = el.getBoundingClientRect();
            tip.style.left = (r.left + r.width / 2) + 'px';
            tip.style.top = r.top + 'px';
            tip.classList.add('visible');
            // Clamp within viewport so long tooltips near the edges don't clip
            const tipRect = tip.getBoundingClientRect();
            const vw = window.innerWidth;
            const margin = 10;
            let dx = 0;
            if (tipRect.right > vw - margin) dx = tipRect.right - (vw - margin);
            else if (tipRect.left < margin) dx = tipRect.left - margin;
            if (dx !== 0) tip.style.left = (parseFloat(tip.style.left) - dx) + 'px';
        }
        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, c =>
                ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }
        document.addEventListener('mouseover', e => {
            const el = e.target.closest('[data-tip]');
            if (el) place(el);
        });
        document.addEventListener('mouseout', e => {
            const el = e.target.closest('[data-tip]');
            if (el) tip.classList.remove('visible');
        });
        document.addEventListener('scroll', () => tip.classList.remove('visible'), true);
    })();

    const activeFilters = { category: new Set(), platform: new Set(), geo: new Set(), restriction: new Set() };
    const totalOffers = <?= $total_offers ?>;

    document.querySelectorAll('.filter-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            const { filter, value } = chip.dataset;
            if (activeFilters[filter].has(value)) {
                activeFilters[filter].delete(value);
                chip.classList.remove('active');
            } else {
                activeFilters[filter].add(value);
                chip.classList.add('active');
            }
            applyFilters();
        });
    });

    const searchInput = document.getElementById('offerSearch');
    if (searchInput) searchInput.addEventListener('input', applyFilters);

    function applyFilters() {
        const q = (searchInput?.value || '').toLowerCase().trim();
        const rows = document.querySelectorAll('#offersBody tr');
        let visible = 0;
        rows.forEach(row => {
            const matches =
                (activeFilters.category.size === 0    || activeFilters.category.has(row.dataset.category)) &&
                (activeFilters.platform.size === 0    || activeFilters.platform.has(row.dataset.platform)) &&
                (activeFilters.geo.size === 0         || activeFilters.geo.has(row.dataset.geo)) &&
                (activeFilters.restriction.size === 0 || activeFilters.restriction.has(row.dataset.restriction)) &&
                (!q || (row.dataset.search || '').includes(q));
            row.style.display = matches ? '' : 'none';
            if (matches) visible++;
        });
        const countEl = document.getElementById('visibleCount');
        if (countEl) countEl.textContent = `(${visible}${visible !== totalOffers ? ' of ' + totalOffers : ''})`;

        const noMatches = document.getElementById('noMatches');
        if (noMatches) noMatches.classList.toggle('hidden', visible !== 0 || totalOffers === 0);

        const clearBtn = document.getElementById('clearFilters');
        const anyActive = Object.values(activeFilters).some(s => s.size > 0) || q !== '';
        if (clearBtn) clearBtn.classList.toggle('hidden', !anyActive);
    }

    function clearAllFilters() {
        Object.values(activeFilters).forEach(s => s.clear());
        document.querySelectorAll('.filter-chip.active').forEach(c => c.classList.remove('active'));
        if (searchInput) searchInput.value = '';
        applyFilters();
    }
</script>
</body>
</html>
