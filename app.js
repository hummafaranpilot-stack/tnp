let landers = [];
let editingId = null;
let currentShaverDomainId = null; // set when opening form from a Shaver suggestion

function getNextSr() {
    const el = document.querySelector('main[data-next-sr]');
    return el ? Number(el.dataset.nextSr) || 1 : 1;
}

function ensureSelectOption(selectId, value) {
    const select = document.getElementById(selectId);
    if (!select || !value) return;
    if (![...select.options].some(o => o.value === value)) {
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = value;
        select.appendChild(opt);
    }
}

function markPrefilled(elId) {
    const el = document.getElementById(elId);
    if (!el) return;
    el.classList.add('prefilled');
    const clear = () => {
        el.classList.remove('prefilled');
        el.removeEventListener('input', clear);
        el.removeEventListener('change', clear);
    };
    el.addEventListener('input', clear);
    el.addEventListener('change', clear);
}

function clearAllPrefilled() {
    document.querySelectorAll('.prefilled').forEach(el => el.classList.remove('prefilled'));
    document.querySelectorAll('.field-hint').forEach(h => {
        h.textContent = '';
        h.classList.remove('visible');
    });
}

function setHint(hintId, text) {
    const h = document.getElementById(hintId);
    if (!h) return;
    h.textContent = text;
    h.classList.add('visible');
}

function clearHint(hintId) {
    const h = document.getElementById(hintId);
    if (!h) return;
    h.textContent = '';
    h.classList.remove('visible');
}

function openForm(offer = null) {
    const isEdit = offer && offer.id;
    const isFromShaver = offer && !isEdit && offer.shaver_domain_id;
    editingId = isEdit ? offer.id : null;
    currentShaverDomainId = isFromShaver ? Number(offer.shaver_domain_id) : null;

    document.getElementById('modalTitle').textContent = isEdit ? 'Edit Offer' : 'Add New Offer';
    document.getElementById('f_id').value = isEdit ? offer.id : '';
    document.getElementById('f_sr').value = (offer?.sr && offer.sr > 0) ? offer.sr : getNextSr();

    ensureSelectOption('f_platform', offer?.platform || '');
    ensureSelectOption('f_category', offer?.category || '');
    ensureSelectOption('f_allowed_geos', offer?.allowed_geos || '');
    document.getElementById('f_platform').value = offer?.platform || 'BuyGoods';
    document.getElementById('f_offer_name').value = offer?.offer_name || '';
    document.getElementById('f_offer_id').value = offer?.offer_id || '';
    document.getElementById('f_category').value = offer?.category || 'Weight Loss';
    document.getElementById('f_revshare').value = offer?.revshare || '';
    document.getElementById('f_cpa').value = offer?.cpa || '';
    document.getElementById('f_allowed_geos').value = offer?.allowed_geos || 'Tier-1 Default';
    document.getElementById('f_restriction').value = offer?.restriction || 'No';
    document.getElementById('f_affiliate_page_url').value = offer?.affiliate_page_url || '';

    // Image reset
    const imgUrl = offer?.image_url || '';
    document.getElementById('f_image_url').value = imgUrl;
    document.getElementById('f_image_file').value = '';
    document.getElementById('uploadStatus').textContent = '';
    switchImageTab('url');
    if (imgUrl) showPreview(imgUrl); else hidePreview();

    // Landers from offer (editing)
    landers = [];
    if (offer?.top_landers) {
        try {
            const parsed = typeof offer.top_landers === 'string' ? JSON.parse(offer.top_landers) : offer.top_landers;
            if (Array.isArray(parsed)) landers = parsed;
        } catch (_) {}
    }
    renderLanders();

    // Reset prefilled state
    clearAllPrefilled();

    // Mark Shaver-sourced fields as prefilled (yellow)
    if (isFromShaver) {
        if (offer.offer_name)         markPrefilled('f_offer_name');
        if (offer.platform)           markPrefilled('f_platform');
        if (offer.affiliate_page_url) markPrefilled('f_affiliate_page_url');
    }

    // Apply platform defaults from past offers (learning)
    applyPlatformDefaults(document.getElementById('f_platform').value, !isEdit);

    // Async fetch top landers + affiliate URL for Shaver suggestions
    if (isFromShaver) {
        fetchTopLandersAsync(offer.shaver_domain_id);
    }

    document.getElementById('modal').classList.remove('hidden');
}

function applyPlatformDefaults(platform, isNew) {
    if (!isNew) return;
    const defaults = (window.PLATFORM_DEFAULTS || {})[platform];
    if (!defaults) return;

    const fields = ['revshare', 'cpa', 'allowed_geos', 'restriction', 'category'];
    const initial = {
        allowed_geos: 'Tier-1 Default',
        restriction: 'No',
        category: 'Weight Loss',
    };

    fields.forEach(field => {
        const el = document.getElementById('f_' + field);
        if (!el || !defaults[field]) return;
        const current = (el.value || '').trim();
        const isDefaultOrEmpty = current === '' || current === (initial[field] || '');
        if (!isDefaultOrEmpty) return;

        if (el.tagName === 'SELECT') ensureSelectOption('f_' + field, defaults[field]);
        el.value = defaults[field];
        el.classList.add('prefilled');
        setHint('hint_' + field, 'from past ' + platform + ' offers');

        const clear = () => {
            el.classList.remove('prefilled');
            clearHint('hint_' + field);
            el.removeEventListener('input', clear);
            el.removeEventListener('change', clear);
        };
        el.addEventListener('input', clear);
        el.addEventListener('change', clear);
    });
}

// React to platform dropdown changes — re-apply learned defaults
document.addEventListener('DOMContentLoaded', () => {
    const platformSel = document.getElementById('f_platform');
    if (platformSel) {
        platformSel.addEventListener('change', () => {
            // Only re-apply if we're adding (not editing)
            if (!editingId) applyPlatformDefaults(platformSel.value, true);
        });
    }
});

async function fetchTopLandersAsync(domain_id) {
    setHint('hint_landers', 'loading from Shaver…');
    try {
        const res = await fetch('/api.php?action=top_landers', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ domain_id }),
        });
        const result = await res.json();
        if (!result.ok) {
            clearHint('hint_landers');
            return;
        }
        // Affiliate URL detected from traffic (contains "afftools"/"affiliate")
        if (result.affiliate_url) {
            const aff = document.getElementById('f_affiliate_page_url');
            if (!aff.value.trim() || aff.classList.contains('prefilled')) {
                aff.value = result.affiliate_url;
                markPrefilled('f_affiliate_page_url');
            }
        }
        // Top landers (overwrites only if no manual edits yet)
        if (Array.isArray(result.landers) && result.landers.length > 0) {
            // Only auto-replace if user hasn't added/edited landers manually
            const allPrefilled = landers.every(l => l.prefilled);
            if (allPrefilled) {
                landers = result.landers.map(l => ({ label: l.label, url: l.url, prefilled: true }));
                renderLanders();
                setHint('hint_landers', result.landers.length + ' from Shaver (all-time traffic)');
            } else {
                clearHint('hint_landers');
            }
        } else {
            clearHint('hint_landers');
        }
    } catch (e) {
        console.error('Top landers fetch failed', e);
        clearHint('hint_landers');
    }
}

function closeForm() {
    document.getElementById('modal').classList.add('hidden');
    editingId = null;
    landers = [];
    clearAllPrefilled();
}

function switchImageTab(which) {
    document.querySelectorAll('.image-tabs .tab').forEach(t =>
        t.classList.toggle('active', t.dataset.tab === which));
    document.getElementById('imageUrlPane').classList.toggle('hidden', which !== 'url');
    document.getElementById('imageUploadPane').classList.toggle('hidden', which !== 'upload');
}

function previewImage(url) {
    if (url && url.trim()) showPreview(url.trim()); else hidePreview();
}

function showPreview(url) {
    const box = document.getElementById('imagePreview');
    document.getElementById('previewImg').src = url;
    box.classList.remove('hidden');
}

function hidePreview() {
    document.getElementById('imagePreview').classList.add('hidden');
    document.getElementById('previewImg').src = '';
}

function clearImage() {
    document.getElementById('f_image_url').value = '';
    document.getElementById('f_image_file').value = '';
    document.getElementById('uploadStatus').textContent = '';
    hidePreview();
}

async function uploadImage(input) {
    const file = input.files?.[0];
    if (!file) return;
    const status = document.getElementById('uploadStatus');
    status.textContent = 'Uploading…';
    status.className = 'hint';

    const fd = new FormData();
    fd.append('image', file);
    try {
        const res = await fetch('/api.php?action=upload', { method: 'POST', body: fd });
        const result = await res.json();
        if (result.ok && result.url) {
            document.getElementById('f_image_url').value = result.url;
            showPreview(result.url);
            status.textContent = '✓ Uploaded';
            status.className = 'hint success';
        } else {
            status.textContent = 'Upload failed: ' + (result.error || 'Unknown error');
            status.className = 'hint error';
        }
    } catch (e) {
        status.textContent = 'Upload failed: ' + e.message;
        status.className = 'hint error';
    }
}

function addLander() {
    const label = document.getElementById('lander_label').value.trim();
    const url = document.getElementById('lander_url').value.trim();
    if (!label || !url) return;
    landers.push({ label, url });
    document.getElementById('lander_label').value = '';
    document.getElementById('lander_url').value = '';
    renderLanders();
}

function removeLander(i) {
    landers.splice(i, 1);
    renderLanders();
}

function moveLander(i, delta) {
    const j = i + delta;
    if (j < 0 || j >= landers.length) return;
    [landers[i], landers[j]] = [landers[j], landers[i]];
    renderLanders();
}

function deriveLanderType(url) {
    const lower = (url || '').toLowerCase();
    let m;
    if ((m = lower.match(/\/dtc(\d*)(\/|$)/))) {
        return {
            label: 'DTC' + m[1],
            type: 'dtc',
            advice: 'prelander',
            description: 'Direct-to-consumer page with pricing cards and minimal content. Best paired with a prelander — the prelander warms the customer up, then they land here ready to pick a pack and buy.',
        };
    }
    if ((m = lower.match(/\/(long|best)(\d*)(\/|$)/))) {
        return {
            label: m[1].charAt(0).toUpperCase() + m[1].slice(1) + ' TSL' + m[2],
            type: 'long',
            advice: 'direct-link',
            description: 'Long-form sales page (Text Sales Letter) with full copy, graphics, and videos. It already does the "brainwashing" on its own — no prelander needed. Direct-link your traffic straight here.',
        };
    }
    if ((m = lower.match(/\/short(\d*)(\/|$)/))) {
        return {
            label: 'Short TSL' + m[1],
            type: 'short',
            advice: 'prelander',
            description: 'Medium-length page — lighter copy than a full TSL. Works best with a prelander that hooks the reader first, so cold traffic gets warmed up before landing here.',
        };
    }
    return { label: '', type: 'other', advice: '', description: '' };
}

function renderLanders() {
    const box = document.getElementById('landersList');
    box.innerHTML = '';
    landers.forEach((l, i) => {
        const row = document.createElement('div');
        row.className = 'lander-item' + (l.prefilled ? ' prefilled' : '');
        const derived = deriveLanderType(l.url);
        const label = derived.label || l.label || 'Lander';
        const type = derived.type !== 'other' ? derived.type : (l.type || 'other');
        const advice = derived.advice || l.advice || '';
        const description = derived.description || '';
        const visitsBadge = l.visits ? `<span class="visits-tag">${l.visits} visits</span>` : '';
        const adviceChip = advice
            ? `<span class="advice-chip advice-${type}"${description ? ` data-tip="${escapeHtml(description)}"` : ''}>${escapeHtml(advice)}</span>`
            : '';
        const upDisabled = i === 0 ? 'disabled' : '';
        const downDisabled = i === landers.length - 1 ? 'disabled' : '';
        row.innerHTML = `
            <div class="lander-reorder">
                <button type="button" class="reorder-btn" ${upDisabled} onclick="moveLander(${i}, -1)" title="Move up" aria-label="Move up">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                </button>
                <button type="button" class="reorder-btn" ${downDisabled} onclick="moveLander(${i}, 1)" title="Move down" aria-label="Move down">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
            </div>
            <div class="lander-main">
                <span class="label">${escapeHtml(label)}${adviceChip}${visitsBadge}</span>
                <span class="url">${escapeHtml(l.url)}</span>
            </div>
            <button type="button" class="remove" onclick="removeLander(${i})" title="Remove">&times;</button>
        `;
        box.appendChild(row);
    });
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

async function submitForm(e) {
    e.preventDefault();
    const payload = {
        id: editingId,
        sr: Number(document.getElementById('f_sr').value) || 0,
        platform: document.getElementById('f_platform').value,
        offer_name: document.getElementById('f_offer_name').value,
        image_url: document.getElementById('f_image_url').value.trim(),
        offer_id: document.getElementById('f_offer_id').value,
        category: document.getElementById('f_category').value,
        revshare: document.getElementById('f_revshare').value,
        cpa: document.getElementById('f_cpa').value,
        allowed_geos: document.getElementById('f_allowed_geos').value,
        restriction: document.getElementById('f_restriction').value,
        affiliate_page_url: document.getElementById('f_affiliate_page_url').value,
        top_landers: landers.map(({ label, url }) => ({ label, url })),
        shaver_domain_id: editingId ? null : currentShaverDomainId,
    };

    const action = editingId ? 'update' : 'create';
    const res = await fetch(`/api.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    });
    const result = await res.json();
    if (result.ok) {
        window.location.reload();
    } else {
        alert('Save failed: ' + (result.error || 'Unknown error'));
    }
}

function editOffer(btn) {
    const row = btn.closest('tr');
    const offer = JSON.parse(row.dataset.offer);
    openForm(offer);
}

async function deleteOffer(btn) {
    const row = btn.closest('tr');
    const offer = JSON.parse(row.dataset.offer);
    if (!confirm(`Delete "${offer.offer_name}"? This cannot be undone.`)) return;
    const res = await fetch('/api.php?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: offer.id }),
    });
    const result = await res.json();
    if (result.ok) window.location.reload();
    else alert('Delete failed: ' + (result.error || 'Unknown error'));
}

// Custom hover tooltip for data-tip elements (used by advice chips)
(function initTooltip() {
    if (typeof document === 'undefined' || document.querySelector('.custom-tooltip')) return;
    const tip = document.createElement('div');
    tip.className = 'custom-tooltip';
    document.body.appendChild(tip);
    function place(el) {
        const text = el.getAttribute('data-tip');
        if (!text) return;
        tip.innerHTML = '';
        const parts = text.split(' — ');
        if (parts.length > 1) {
            const head = document.createElement('strong');
            head.textContent = parts[0];
            const br = document.createElement('br');
            const rest = document.createTextNode(parts.slice(1).join(' — '));
            tip.appendChild(head);
            tip.appendChild(br);
            tip.appendChild(rest);
        } else {
            tip.textContent = text;
        }
        const r = el.getBoundingClientRect();
        tip.style.left = (r.left + r.width / 2) + 'px';
        tip.style.top = r.top + 'px';
        tip.classList.add('visible');
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

async function dismissSuggestion(event, btn) {
    event.stopPropagation();
    event.preventDefault();
    const wrap = btn.closest('.suggest-card-wrap');
    const id = Number(wrap?.dataset.shaverId || 0);
    if (!id) return;
    wrap.style.opacity = '0.4';
    try {
        const res = await fetch('/api.php?action=dismiss_suggestion', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ shaver_domain_id: id }),
        });
        const r = await res.json();
        if (r.ok) {
            wrap.style.transition = 'opacity 0.2s';
            wrap.style.opacity = '0';
            setTimeout(() => wrap.remove(), 200);
        } else {
            wrap.style.opacity = '1';
            alert('Dismiss failed: ' + (r.error || 'Unknown error'));
        }
    } catch (e) {
        wrap.style.opacity = '1';
        alert('Dismiss failed: ' + e.message);
    }
}
