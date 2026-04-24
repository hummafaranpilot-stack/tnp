/**
 * Public directory visitor tracker.
 *
 * Beacons scroll-depth / time-on-page / Promote-Now clicks back to
 * /api.php?action=track so the admin Analytics page can show a row
 * per visit with meaningful engagement fields.
 *
 * The visit row is already created by log_visit() in tracker.php, so
 * we only PATCH with the client-side numbers right before the tab
 * closes (pagehide fires more reliably than beforeunload on mobile).
 */
(function () {
    const visitId = window.TNP_VISIT_ID;
    if (!visitId) return;

    const start = Date.now();
    let maxScroll = 0;
    const clicks = [];

    const updateScroll = () => {
        const h = document.documentElement;
        const scrolled = (h.scrollTop + window.innerHeight);
        const total = Math.max(h.scrollHeight, 1);
        const pct = Math.min(100, Math.round((scrolled / total) * 100));
        if (pct > maxScroll) maxScroll = pct;
    };

    updateScroll();
    window.addEventListener('scroll', updateScroll, { passive: true });
    window.addEventListener('resize', updateScroll, { passive: true });

    // Capture clicks on meaningful targets — the Promote Now button per
    // offer is the highest-signal one, but we also log Affiliate Page
    // links and Top Lander clicks for a fuller engagement picture.
    document.addEventListener('click', (e) => {
        const promote = e.target.closest('.btn-promote');
        if (promote) {
            const row = promote.closest('tr');
            clicks.push({
                type: 'promote',
                offer: row?.dataset?.platform
                    ? `${row.dataset.platform} — ${promote.dataset.offerName || ''}`.trim()
                    : (promote.dataset.offerName || 'unknown'),
                at: Math.round((Date.now() - start) / 1000),
            });
            return;
        }
        const lander = e.target.closest('.lander-link');
        if (lander) {
            clicks.push({
                type: 'lander',
                url: lander.getAttribute('href') || '',
                at: Math.round((Date.now() - start) / 1000),
            });
            return;
        }
        const link = e.target.closest('.offer-links .link');
        if (link) {
            clicks.push({
                type: 'link',
                title: link.textContent.trim(),
                url: link.getAttribute('href') || '',
                at: Math.round((Date.now() - start) / 1000),
            });
        }
    });

    const send = () => {
        const payload = {
            visit_id: visitId,
            max_scroll: maxScroll,
            duration_sec: Math.max(1, Math.round((Date.now() - start) / 1000)),
            clicks,
        };
        const body = JSON.stringify(payload);
        // navigator.sendBeacon survives the unload; fetch(keepalive) is a
        // fallback for older browsers. Both use the same endpoint.
        if (navigator.sendBeacon) {
            const blob = new Blob([body], { type: 'application/json' });
            navigator.sendBeacon('/api.php?action=track', blob);
        } else {
            fetch('/api.php?action=track', { method: 'POST', body, keepalive: true, headers: { 'Content-Type': 'application/json' } });
        }
    };

    // pagehide fires on Safari / mobile where beforeunload is flaky.
    // visibilitychange covers the "switched tab then closed" case.
    window.addEventListener('pagehide', send);
    window.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') send();
    });
})();
