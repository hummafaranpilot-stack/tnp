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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1>TrustedNutraProduct</h1>
            <p class="tagline">Affiliate Offer Directory</p>
            <div class="contact">
                <span>✉ contact@trustednutraproduct.com</span>
                <span class="divider">•</span>
                <span>@TrustedNutraProduct</span>
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
