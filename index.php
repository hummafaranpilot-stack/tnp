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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrustedNutraProduct — Affiliate Offers</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <header class="site-header">
        <a class="admin-link" href="/admin" title="Admin Login">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <span>Admin</span>
        </a>
        <div class="container">
            <h1>TrustedNutraProduct</h1>
            <p class="tagline">All Offers Directory <span class="tagline-sub">(For Affiliates)</span></p>
            <div class="contact">
                <a class="contact-link" href="mailto:contact@trustednutraproduct.com">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                    <span>contact@trustednutraproduct.com</span>
                </a>
                <a class="contact-link" href="https://t.me/TrustedNutraProduct" target="_blank" rel="noopener noreferrer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    <span>@TrustedNutraProduct</span>
                </a>
            </div>
        </div>
    </header>

    <main class="container">
        <?php if ($load_error): ?>
            <p class="error-block"><?= h($load_error) ?></p>
        <?php elseif (empty($offers)): ?>
            <div class="empty">No offers available yet.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="offer-table">
                    <thead>
                        <tr>
                            <th>Sr</th>
                            <th>Image</th>
                            <th>Platform</th>
                            <th>Offer Name</th>
                            <th>Offer ID / Nickname</th>
                            <th>Category</th>
                            <th>Top Landers</th>
                            <th>Affiliate / Creative Page</th>
                            <th>RevShare</th>
                            <th>CPA</th>
                            <th>Allowed GEOs</th>
                            <th>Restriction</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($offers as $o):
                        $landers = json_decode($o['top_landers'] ?? '[]', true) ?: [];
                    ?>
                        <tr>
                            <td class="sr"><?= h((string)$o['sr']) ?></td>
                            <td>
                                <?php if (!empty($o['image_url'])): ?>
                                    <img class="thumb" src="<?= h($o['image_url']) ?>" alt="<?= h($o['offer_name']) ?>">
                                <?php else: ?>
                                    <span class="thumb thumb-empty">—</span>
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
                                    <a class="link" href="<?= h($o['affiliate_page_url']) ?>" target="_blank" rel="noopener noreferrer">Click Here</a>
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
            <p class="footer-note">Showing <?= count($offers) ?> offer<?= count($offers) !== 1 ? 's' : '' ?></p>
        <?php endif; ?>
    </main>
</body>
</html>
