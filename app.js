let landers = [];
let editingId = null;

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

    document.getElementById('modalTitle').textContent = isEdit ? 'Edit Offer' : 'Add New Offer';
    document.getElementById('f_id').value = isEdit ? offer.id : '';
    document.getElementById('f_sr').value = (offer?.sr && offer.sr > 0) ? offer.sr : getNextSr();

    ensureSelectOption('f_platform', offer?.platform || '');
    ensureSelectOption('f_category', offer?.category || '');
    document.getElementById('f_platform').value = offer?.platform || 'BuyGoods';
    document.getElementById('f_offer_name').value = offer?.offer_name || '';
    document.getElementById('f_offer_id').value = offer?.offer_id || '';
    document.getElementById('f_category').value = offer?.category || 'Weight Loss';
    document.getElementById('f_revshare').value = offer?.revshare || '';
    document.getElementById('f_cpa').value = offer?.cpa || '';
    document.getElementById('f_allowed_geos').value = offer?.allowed_geos || 'Tier-1';
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
        allowed_geos: 'Tier-1',
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

function renderLanders() {
    const box = document.getElementById('landersList');
    box.innerHTML = '';
    landers.forEach((l, i) => {
        const row = document.createElement('div');
        row.className = 'lander-item' + (l.prefilled ? ' prefilled' : '');
        const visitsBadge = l.visits ? ` <span class="visits-tag">${l.visits} visits</span>` : '';
        row.innerHTML = `
            <span class="label">${escapeHtml(l.label)}${visitsBadge}</span>
            <span class="url">${escapeHtml(l.url)}</span>
            <button type="button" class="remove" onclick="removeLander(${i})">&times;</button>
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
