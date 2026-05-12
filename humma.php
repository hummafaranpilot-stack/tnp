<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/tracker.php';

// Log the pageview (same beacon scheme as the directory).
$__visit = log_visit();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Humma Faran — CEO &amp; Founder, TrustedNutraProduct</title>
    <meta name="description" content="Affiliate &amp; Advertiser opportunities with Humma Faran, CEO of TrustedNutraProduct. Warm traffic across Email, Search, SEO, PPC.">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/style.css?v=<?= @filemtime(__DIR__ . '/style.css') ?: time() ?>">
    <style>
        /* Page-only chrome — reuses the global tokens from style.css */
        body.profile-page {
            background:
                radial-gradient(1100px 600px at 90% -10%, rgba(99, 102, 241, 0.18), transparent 60%),
                radial-gradient(900px 500px at -10% 10%, rgba(56, 189, 248, 0.16), transparent 60%),
                linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            min-height: 100vh;
        }
        .profile-wrap {
            max-width: 880px;
            margin: 0 auto;
            padding: 3rem 1.5rem 4rem;
        }
        .profile-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 25px 55px -28px rgba(15, 26, 53, 0.45), 0 8px 20px -10px rgba(15, 26, 53, 0.12);
            overflow: hidden;
        }
        .profile-hero {
            position: relative;
            padding: 2.25rem 2rem 1.5rem;
            background: linear-gradient(135deg, #0f1a35 0%, #1e293b 55%, #312e81 100%);
            color: #fff;
            text-align: center;
        }
        .profile-hero::before {
            content: '';
            position: absolute; inset: 0;
            background:
                radial-gradient(420px 280px at 80% 30%, rgba(129, 140, 248, 0.32), transparent 65%),
                radial-gradient(380px 240px at 20% 90%, rgba(56, 189, 248, 0.22), transparent 65%);
            pointer-events: none;
        }
        .profile-photo-wrap {
            position: relative;
            width: 144px; height: 144px;
            margin: 0 auto 1.1rem;
            border-radius: 50%;
            padding: 4px;
            background: linear-gradient(135deg, #6366f1, #22d3ee);
            box-shadow: 0 16px 36px rgba(99, 102, 241, 0.35);
        }
        .profile-photo {
            width: 100%; height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            background: #fff;
            display: block;
        }
        .profile-name {
            position: relative;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.01em;
            margin: 0 0 6px;
        }
        .profile-title {
            position: relative;
            font-size: 14px;
            color: rgba(226, 232, 240, 0.92);
            margin: 0 0 0.4rem;
            font-weight: 500;
        }
        .profile-brand {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            font-size: 12px;
            font-weight: 600;
            color: #fff;
            text-decoration: none;
            transition: background 0.15s;
        }
        .profile-brand:hover { background: rgba(255, 255, 255, 0.2); }
        .profile-contacts {
            position: relative;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 1.2rem;
        }
        .pc-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.92);
            color: #0f1a35;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: transform 0.15s, box-shadow 0.15s, background 0.15s;
            box-shadow: 0 6px 14px rgba(15, 26, 53, 0.18);
        }
        .pc-btn:hover {
            transform: translateY(-1px);
            background: #fff;
            box-shadow: 0 10px 22px rgba(15, 26, 53, 0.25);
        }
        .pc-btn-telegram {
            background: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%);
            color: #fff;
        }
        .pc-btn-telegram:hover { background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%); }

        /* Tabs */
        .tab-bar {
            display: flex;
            gap: 0;
            padding: 0;
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
            border-bottom: 1px solid var(--border);
        }
        .tab-btn {
            flex: 1;
            padding: 1.05rem 0.5rem;
            background: transparent;
            border: none;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.01em;
            color: var(--text-muted);
            cursor: pointer;
            position: relative;
            transition: color 0.15s;
        }
        .tab-btn:hover { color: var(--text); }
        .tab-btn.active { color: #312e81; }
        .tab-btn.active::after {
            content: '';
            position: absolute; left: 25%; right: 25%; bottom: -1px;
            height: 3px;
            border-radius: 3px 3px 0 0;
            background: linear-gradient(90deg, #6366f1, #22d3ee);
        }
        .tab-icon {
            display: inline-block;
            margin-right: 6px;
            vertical-align: -3px;
        }

        .tab-body {
            padding: 1.75rem 2rem 2.25rem;
        }
        .tab-pane { display: none; animation: fadeIn 0.25s ease; }
        .tab-pane.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

        .tab-intro { font-size: 15px; line-height: 1.65; color: var(--text); margin: 0 0 1.25rem; }
        .tab-intro strong { color: #0f1a35; font-weight: 700; }

        .capabilities {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin: 1.25rem 0 1.5rem;
        }
        .cap {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
        }
        .cap-icon {
            display: inline-flex;
            align-items: center; justify-content: center;
            width: 30px; height: 30px;
            border-radius: 8px;
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            color: #4338ca;
            flex-shrink: 0;
        }

        .channel-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 8px;
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem;
        }
        .channel-list li {
            display: flex; align-items: center; gap: 8px;
            padding: 9px 12px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
        }
        .channel-list li .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.18);
        }

        .pitch-callout {
            margin-top: 1.5rem;
            padding: 16px 18px;
            border-radius: 14px;
            background: linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 100%);
            border: 1px solid #a7f3d0;
            color: #064e3b;
            font-size: 13.5px;
            line-height: 1.6;
        }
        .pitch-callout strong { color: #047857; }

        .advert-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin: 1.25rem 0 1.5rem;
        }
        .advert-stat {
            padding: 14px 14px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid var(--border);
            text-align: center;
        }
        .advert-stat .v {
            font-size: 22px;
            font-weight: 800;
            color: #312e81;
            letter-spacing: -0.01em;
        }
        .advert-stat .l {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-top: 2px;
        }

        .cta-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 1.25rem;
        }
        .cta-primary, .cta-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none;
            transition: transform 0.15s, box-shadow 0.15s, filter 0.15s;
        }
        .cta-primary {
            background: linear-gradient(135deg, #0f1a35 0%, #312e81 100%);
            color: #fff;
            box-shadow: 0 8px 18px rgba(49, 46, 129, 0.32);
        }
        .cta-primary:hover { transform: translateY(-1px); filter: brightness(1.08); }
        .cta-secondary {
            background: #fff;
            color: #312e81;
            border: 1px solid #c7d2fe;
        }
        .cta-secondary:hover { background: #eef2ff; }

        .footer-line {
            text-align: center;
            font-size: 12px;
            color: var(--text-subtle);
            margin-top: 2rem;
        }

        @media (max-width: 600px) {
            .profile-wrap { padding: 1.5rem 1rem 3rem; }
            .profile-hero { padding: 1.75rem 1.25rem 1.25rem; }
            .profile-photo-wrap { width: 120px; height: 120px; }
            .profile-name { font-size: 24px; }
            .tab-body { padding: 1.25rem 1.25rem 1.75rem; }
            .tab-btn { font-size: 13px; padding: 0.85rem 0.25rem; }
        }
    </style>
</head>
<body class="profile-page">

<div class="profile-wrap">
    <div class="profile-card">
        <!-- Hero -->
        <div class="profile-hero">
            <div class="profile-photo-wrap">
                <img class="profile-photo" src="/assets/humma.jpg" alt="Humma Faran">
            </div>
            <h1 class="profile-name">Humma Faran</h1>
            <p class="profile-title">CEO &amp; Founder</p>
            <a class="profile-brand" href="/" target="_blank" rel="noopener noreferrer">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 6v6c0 5 3.5 9 8 10 4.5-1 8-5 8-10V6l-8-4z"/></svg>
                TrustedNutraProduct
            </a>

            <div class="profile-contacts">
                <a class="pc-btn pc-btn-telegram" href="https://t.me/TrustedNutraProduct" target="_blank" rel="noopener noreferrer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    Message on Telegram
                </a>
                <a class="pc-btn" href="mailto:contact@trustednutraproduct.com">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                    Email
                </a>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tab-bar" role="tablist">
            <button class="tab-btn active" id="tab-affiliate" role="tab" aria-selected="true" aria-controls="pane-affiliate" onclick="switchTab('affiliate')">
                <svg class="tab-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                Affiliate
            </button>
            <button class="tab-btn" id="tab-advertiser" role="tab" aria-selected="false" aria-controls="pane-advertiser" onclick="switchTab('advertiser')">
                <svg class="tab-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
                Advertiser
            </button>
        </div>

        <div class="tab-body">

            <!-- AFFILIATE PANE -->
            <div class="tab-pane active" id="pane-affiliate" role="tabpanel" aria-labelledby="tab-affiliate">
                <p class="tab-intro">
                    <strong>We're open to running offers.</strong> Our team actively
                    pushes nutra and broader vertical offers with proven creatives,
                    pre-warmed traffic, and conversion-tested funnels. Send your
                    top offers — we'll evaluate fit and move quickly to test.
                </p>

                <div class="capabilities">
                    <div class="cap">
                        <span class="cap-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                        </span>
                        Warm Audiences
                    </div>
                    <div class="cap">
                        <span class="cap-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18"/><path d="M12 3a14 14 0 0 0 0 18"/></svg>
                        </span>
                        Seasoned Pixels
                    </div>
                    <div class="cap">
                        <span class="cap-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        </span>
                        Fast Testing
                    </div>
                    <div class="cap">
                        <span class="cap-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/></svg>
                        </span>
                        Scale Ready
                    </div>
                </div>

                <p class="tab-intro" style="margin-bottom: 0.5rem;">
                    <strong>Traffic channels we run on:</strong>
                </p>
                <ul class="channel-list">
                    <li><span class="dot"></span>Email</li>
                    <li><span class="dot"></span>Search</li>
                    <li><span class="dot"></span>SEO</li>
                    <li><span class="dot"></span>PPC</li>
                </ul>

                <div class="pitch-callout">
                    <strong>Have an offer to share?</strong> Send the offer page,
                    payout terms, allowed GEOs and any restrictions — we'll review
                    and respond on Telegram within 24 hours.
                </div>

                <div class="cta-row">
                    <a class="cta-primary" href="https://t.me/TrustedNutraProduct" target="_blank" rel="noopener noreferrer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23z"/></svg>
                        Pitch your offer
                    </a>
                    <a class="cta-secondary" href="mailto:contact@trustednutraproduct.com?subject=Offer%20pitch%20for%20TrustedNutraProduct">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                        Email us
                    </a>
                </div>
            </div>

            <!-- ADVERTISER PANE -->
            <div class="tab-pane" id="pane-advertiser" role="tabpanel" aria-labelledby="tab-advertiser">
                <p class="tab-intro">
                    <strong>We own and operate proven nutra offers</strong> across
                    weight loss, male enhancement, blood sugar and more — running on
                    BuyGoods, ClickBank, Digistore24 and MaxWeb. Affiliates get
                    long-form TSLs, prelanders, VSLs, ad-copy swipes and a real
                    affiliate manager who picks up the phone.
                </p>

                <div class="advert-stats">
                    <div class="advert-stat"><div class="v">Tier-1</div><div class="l">GEOs</div></div>
                    <div class="advert-stat"><div class="v">$170+</div><div class="l">Avg CPA</div></div>
                    <div class="advert-stat"><div class="v">75 – 80%</div><div class="l">RevShare</div></div>
                    <div class="advert-stat"><div class="v">$5+</div><div class="l">EPC</div></div>
                </div>

                <p class="tab-intro" style="margin-bottom: 0.5rem;">
                    <strong>What's in it for affiliates:</strong>
                </p>
                <ul class="channel-list">
                    <li><span class="dot"></span>Multiple Landers</li>
                    <li><span class="dot"></span>High AOV</li>
                    <li><span class="dot"></span>CPA + RevShare</li>
                    <li><span class="dot"></span>Creatives Ready</li>
                </ul>

                <div class="pitch-callout">
                    <strong>Browse the full directory</strong> — every active offer
                    with payouts, allowed GEOs, top landers and a Promote Now
                    hoplink builder. Better terms available on request: ping us on
                    Telegram and we'll bump your CPA / RevShare after review.
                </div>

                <div class="cta-row">
                    <a class="cta-primary" href="/">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                        Browse Offers Directory
                    </a>
                    <a class="cta-secondary" href="https://t.me/TrustedNutraProduct" target="_blank" rel="noopener noreferrer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23z"/></svg>
                        Talk to AM
                    </a>
                </div>
            </div>
        </div>
    </div>

    <p class="footer-line">© <?= date('Y') ?> TrustedNutraProduct · Affiliate &amp; Advertiser Hub</p>
</div>

<script>
function switchTab(which) {
    document.querySelectorAll('.tab-btn').forEach(b => {
        const on = b.id === 'tab-' + which;
        b.classList.toggle('active', on);
        b.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    document.querySelectorAll('.tab-pane').forEach(p => {
        p.classList.toggle('active', p.id === 'pane-' + which);
    });
    // Drop the chosen tab into the hash so refresh / share-link preserves view.
    history.replaceState(null, '', '#' + which);
}

// On load, honor the URL hash so /humma#advertiser opens that tab.
(function () {
    const h = (location.hash || '').replace('#', '');
    if (h === 'affiliate' || h === 'advertiser') switchTab(h);
})();
</script>
<?php if (!empty($__visit['visit_id'])): ?>
<script>window.TNP_VISIT_ID = <?= (int)$__visit['visit_id'] ?>;</script>
<script src="/tracker.js?v=<?= @filemtime(__DIR__ . '/tracker.js') ?: time() ?>" defer></script>
<?php endif; ?>
</body>
</html>
