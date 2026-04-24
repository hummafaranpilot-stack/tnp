<?php
require __DIR__ . '/includes/auth.php';
require_login();
require __DIR__ . '/includes/db.php';

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
            <span class="brand-logo">TNP</span>
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
                                <th>Image</th>
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
                        <?php foreach ($offers as $o):
                            $search_text = strtolower(implode(' ', [
                                $o['offer_name'], $o['offer_id'], $o['platform'],
                                $o['category'], $o['allowed_geos']
                            ]));
                        ?>
                            <tr data-id="<?= h((string)$o['id']) ?>" data-search="<?= h($search_text) ?>" data-offer='<?= h(json_encode($o, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>
                                <td class="sr"><?= h((string)$o['sr']) ?></td>
                                <td>
                                    <?php if (!empty($o['image_url'])): ?>
                                        <img class="thumb" src="<?= h($o['image_url']) ?>" alt="">
                                    <?php else: ?>
                                        <span class="thumb-empty">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-slate"><?= h($o['platform']) ?></span></td>
                                <td class="name"><?= h($o['offer_name']) ?></td>
                                <td><span class="pill pill-slate"><?= h($o['category']) ?></span></td>
                                <td class="rev"><?= h($o['revshare']) ?></td>
                                <td><?= h($o['cpa']) ?></td>
                                <td class="muted"><?= h($o['allowed_geos']) ?></td>
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
            <div class="grid">
                <div><label>Sr #</label><input type="number" name="sr" id="f_sr" required></div>
                <div><label>Platform</label>
                    <select name="platform" id="f_platform">
                        <option>BuyGoods</option><option>ClickBank</option><option>Other</option>
                    </select>
                </div>
                <div class="full"><label>Offer Name</label><input type="text" name="offer_name" id="f_offer_name" required></div>

                <div class="full">
                    <label>Product Image</label>
                    <div class="image-tabs">
                        <button type="button" class="tab active" data-tab="url" onclick="switchImageTab('url')">Paste URL</button>
                        <button type="button" class="tab" data-tab="upload" onclick="switchImageTab('upload')">Upload File</button>
                    </div>
                    <div id="imageUrlPane" class="tab-pane">
                        <input type="url" id="f_image_url" placeholder="https://example.com/image.jpg" oninput="previewImage(this.value)">
                    </div>
                    <div id="imageUploadPane" class="tab-pane hidden">
                        <input type="file" id="f_image_file" accept="image/png,image/jpeg,image/webp,image/gif" onchange="uploadImage(this)">
                        <p class="hint" id="uploadStatus"></p>
                    </div>
                    <div id="imagePreview" class="image-preview hidden">
                        <img id="previewImg" src="" alt="">
                        <button type="button" class="remove-img" onclick="clearImage()" title="Remove image">&times;</button>
                    </div>
                </div>

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
                <button type="button" class="btn btn-secondary" onclick="closeForm()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Offer</button>
            </div>
        </form>
    </div>
</div>

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
</script>
</body>
</html>
