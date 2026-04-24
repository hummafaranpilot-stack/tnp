<?php
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/includes/db.php';

$offers = [];
try {
    $pdo = get_pdo();
    $offers = $pdo->query('SELECT * FROM offers ORDER BY sr ASC')->fetchAll();
} catch (Throwable $e) {
    // Show empty; errors handled inline
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TNP Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <header class="site-header admin-header">
        <div class="container admin-header-inner">
            <div>
                <h1 class="small">TNP Admin Dashboard</h1>
                <p class="tagline">TrustedNutraProduct</p>
            </div>
            <div class="admin-actions">
                <a class="link-light" href="/">← View Public Page</a>
                <a class="btn btn-ghost" href="/logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="admin-bar">
            <h2>Offers <span class="count">(<?= count($offers) ?>)</span></h2>
            <button class="btn btn-primary" onclick="openForm()">+ Add Offer</button>
        </div>

        <?php if (empty($offers)): ?>
            <div class="empty dashed">No offers yet. Click "Add Offer" to get started.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="offer-table">
                    <thead>
                        <tr>
                            <th>Sr</th>
                            <th>Platform</th>
                            <th>Offer Name</th>
                            <th>Category</th>
                            <th>RevShare</th>
                            <th>CPA</th>
                            <th>GEOs</th>
                            <th class="center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="offersBody">
                    <?php foreach ($offers as $o): ?>
                        <tr data-id="<?= h((string)$o['id']) ?>" data-offer='<?= h(json_encode($o, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>
                            <td class="sr"><?= h((string)$o['sr']) ?></td>
                            <td><span class="badge"><?= h($o['platform']) ?></span></td>
                            <td class="name"><?= h($o['offer_name']) ?></td>
                            <td><span class="pill"><?= h($o['category']) ?></span></td>
                            <td class="rev"><?= h($o['revshare']) ?></td>
                            <td><?= h($o['cpa']) ?></td>
                            <td class="muted"><?= h($o['allowed_geos']) ?></td>
                            <td class="center">
                                <button class="btn-small btn-edit" onclick="editOffer(this)">Edit</button>
                                <button class="btn-small btn-delete" onclick="deleteOffer(this)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <!-- Add/Edit Modal -->
    <div id="modal" class="modal hidden">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Offer</h3>
                <button class="modal-close" onclick="closeForm()">&times;</button>
            </div>
            <form id="offerForm" class="modal-body" onsubmit="submitForm(event)">
                <input type="hidden" name="id" id="f_id">
                <div class="grid">
                    <div><label>Sr #</label><input type="number" name="sr" id="f_sr" required></div>
                    <div><label>Platform</label>
                        <select name="platform" id="f_platform">
                            <option>BuyGoods</option><option>ClickBank</option><option>Other</option>
                        </select>
                    </div>
                    <div class="full"><label>Offer Name</label><input type="text" name="offer_name" id="f_offer_name" required></div>
                    <div><label>Offer ID / Nickname</label><input type="text" name="offer_id" id="f_offer_id"></div>
                    <div><label>Category</label>
                        <select name="category" id="f_category">
                            <option>Weight Loss</option><option>Male Enhancement</option>
                            <option>Blood Sugar</option><option>Brain Health</option>
                            <option>Joint Pain</option><option>Other</option>
                        </select>
                    </div>
                    <div><label>RevShare</label><input type="text" name="revshare" id="f_revshare" placeholder="e.g. 75%"></div>
                    <div><label>CPA</label><input type="text" name="cpa" id="f_cpa" placeholder="e.g. $170"></div>
                    <div><label>Allowed GEOs</label><input type="text" name="allowed_geos" id="f_allowed_geos" placeholder="e.g. Tier-1"></div>
                    <div><label>Restriction</label>
                        <select name="restriction" id="f_restriction"><option>No</option><option>Yes</option></select>
                    </div>
                    <div class="full"><label>Affiliate / Creative Page URL</label><input type="url" name="affiliate_page_url" id="f_affiliate_page_url" placeholder="https://..."></div>
                    <div class="full">
                        <label>Top Landers</label>
                        <div class="lander-row">
                            <input type="text" id="lander_label" placeholder="Label (e.g. Lander 1)">
                            <input type="url" id="lander_url" placeholder="https://...">
                            <button type="button" class="btn btn-primary" onclick="addLander()">+ Add</button>
                        </div>
                        <div id="landersList" class="landers-list"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost-dark" onclick="closeForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Offer</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/app.js"></script>
</body>
</html>
