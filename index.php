<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

$offers = [];
$load_error = '';
try {
    $pdo = get_pdo();
    $offers = $pdo->query('SELECT * FROM offers ORDER BY sr ASC')->fetchAll();
} catch (Throwable $e) {
    $load_error = 'Unable to load offers right now. Please try again later.';
}

function platform_class(string $p): string {
    return match ($p) {
        'BuyGoods'  => 'badge badge-blue',
        'ClickBank' => 'badge badge-gray',
        default     => 'badge badge-slate',
    };
}

function category_class(string $c): string {
    return match ($c) {
        'Weight Loss'      => 'pill pill-green',
        'Male Enhancement' => 'pill pill-maroon',
        'Blood Sugar'      => 'pill pill-orange',
        'Brain Health'     => 'pill pill-purple',
        'Joint Pain'       => 'pill pill-yellow',
        default            => 'pill pill-slate',
    };
}

// Compute stats
$total_offers = count($offers);
$unique_categories = count(array_unique(array_filter(array_column($offers, 'category'))));
$unique_platforms = count(array_unique(array_filter(array_column($offers, 'platform'))));
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
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <!-- Top Navigation -->
    <nav class="topnav">
        <div class="container topnav-inner">
            <a class="brand" href="/">
                <span class="brand-logo">TNP</span>
                <span>
                    <span class="brand-name">TrustedNutraProduct</span>
                    <br><span class="brand-tag">Affiliate Directory</span>
                </span>
            </a>
            <div class="nav-actions">
                <a class="icon-link" href="mailto:contact@trustednutraproduct.com" title="Email us">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                    <span>Email</span>
                </a>
                <a class="icon-link" href="https://t.me/TrustedNutraProduct" target="_blank" rel="noopener noreferrer" title="Telegram">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    <span>Telegram</span>
                </a>
                <a class="btn-nav" href="/admin">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Admin
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="container hero-inner">
            <span class="hero-eyebrow">Live Offers · Updated in Real-time</span>
            <h1>All Offers Directory</h1>
            <p class="hero-sub">
                Browse active TrustedNutraProduct affiliate offers — verified platforms, transparent payouts, and direct creative links for partners.
            </p>
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
        </div>
    </section>

    <!-- Main content -->
    <main class="page">
        <div class="container">
            <div class="section-head">
                <h2 class="section-title">Offers <span class="count">(<?= $total_offers ?>)</span></h2>
                <div class="search-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" class="search-input" id="offerSearch" placeholder="Search offers, categories, platforms...">
                </div>
            </div>

            <?php if ($load_error): ?>
                <div class="error-block"><?= h($load_error) ?></div>
            <?php elseif (empty($offers)): ?>
                <div class="empty">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7h-7L10 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/></svg>
                    </div>
                    <p class="empty-title">No offers available yet</p>
                    <p class="empty-text">Check back soon — new offers are added regularly.</p>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="table-wrap">
                        <table class="offer-table">
                            <thead>
                                <tr>
                                    <th>Sr</th>
                                    <th>Image</th>
                                    <th>Platform</th>
                                    <th>Offer Name</th>
                                    <th>Offer ID</th>
                                    <th>Category</th>
                                    <th>Top Landers</th>
                                    <th>Affiliate Page</th>
                                    <th>RevShare</th>
                                    <th>CPA</th>
                                    <th>GEOs</th>
                                    <th>Restriction</th>
                                </tr>
                            </thead>
                            <tbody id="offersBody">
                            <?php foreach ($offers as $o):
                                $landers = json_decode($o['top_landers'] ?? '[]', true) ?: [];
                                $search_text = strtolower(implode(' ', [
                                    $o['offer_name'], $o['offer_id'], $o['platform'],
                                    $o['category'], $o['allowed_geos']
                                ]));
                            ?>
                                <tr data-search="<?= h($search_text) ?>">
                                    <td class="sr"><?= h((string)$o['sr']) ?></td>
                                    <td>
                                        <?php if (!empty($o['image_url'])): ?>
                                            <img class="thumb" src="<?= h($o['image_url']) ?>" alt="<?= h($o['offer_name']) ?>">
                                        <?php else: ?>
                                            <span class="thumb-empty">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="<?= platform_class($o['platform']) ?>"><?= h($o['platform']) ?></span></td>
                                    <td class="name"><?= h($o['offer_name']) ?></td>
                                    <td class="muted"><?= h($o['offer_id']) ?></td>
                                    <td><span class="<?= category_class($o['category']) ?>"><?= h($o['category']) ?></span></td>
                                    <td>
                                        <?php if (empty($landers)): ?>
                                            <span class="muted">—</span>
                                        <?php else: ?>
                                            <div class="landers">
                                            <?php foreach ($landers as $l): ?>
                                                <a href="<?= h($l['url'] ?? '#') ?>" target="_blank" rel="noopener noreferrer"><?= h($l['label'] ?? 'Lander') ?></a>
                                            <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($o['affiliate_page_url'])): ?>
                                            <a class="link" href="<?= h($o['affiliate_page_url']) ?>" target="_blank" rel="noopener noreferrer">
                                                Click Here
                                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17l10-10"/><path d="M7 7h10v10"/></svg>
                                            </a>
                                        <?php else: ?>
                                            <span class="muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="rev"><?= h($o['revshare']) ?></td>
                                    <td><?= h($o['cpa']) ?></td>
                                    <td class="muted"><?= h($o['allowed_geos']) ?></td>
                                    <td class="<?= ($o['restriction'] === 'Yes') ? 'restr-yes' : 'restr-no' ?>"><?= h($o['restriction'] ?: 'No') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p class="footer-note">Showing <?= $total_offers ?> offer<?= $total_offers !== 1 ? 's' : '' ?></p>
            <?php endif; ?>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            &copy; <?= date('Y') ?> TrustedNutraProduct · All offers are independently verified.
        </div>
    </footer>

    <script>
        // Live search filter
        const search = document.getElementById('offerSearch');
        if (search) {
            search.addEventListener('input', e => {
                const q = e.target.value.toLowerCase().trim();
                document.querySelectorAll('#offersBody tr').forEach(row => {
                    const text = row.dataset.search || '';
                    row.style.display = !q || text.includes(q) ? '' : 'none';
                });
            });
        }
    </script>
</body>
</html>
