let landers = [];
let links = [];
let trafficTips = [];
let otherPages = [];          // added "Other Pages" for the current offer
let otherSuggestions = [];    // Shaver suggestions currently shown (session-only)
let editingId = null;
let currentShaverDomainId = null; // set when opening form from a Shaver suggestion
let editingLanderIdx = null;      // index of lander being inline-edited, if any

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

function markPrefilled(elId, hintText) {
    const el = document.getElementById(elId);
    if (!el) return;
    el.classList.add('prefilled');
    const hintId = 'hint_' + elId.replace(/^f_/, '');
    if (hintText) setHint(hintId, hintText);
    const clear = () => {
        el.classList.remove('prefilled');
        clearHint(hintId);
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

    // Links from offer (editing). Fall back to the legacy affiliate_page_url
    // so old records show up as a seed "Affiliate Page" row.
    links = [];
    if (offer?.links) {
        try {
            const parsed = typeof offer.links === 'string' ? JSON.parse(offer.links) : offer.links;
            if (Array.isArray(parsed)) links = parsed;
        } catch (_) {}
    }
    if (links.length === 0 && offer?.affiliate_page_url) {
        links = [{ title: 'Affiliate Page', url: offer.affiliate_page_url }];
    }
    renderLinks();

    // Other Pages (admin-added secondary landers from Shaver suggestions)
    otherPages = [];
    if (offer?.other_pages) {
        try {
            const parsed = typeof offer.other_pages === 'string' ? JSON.parse(offer.other_pages) : offer.other_pages;
            if (Array.isArray(parsed)) otherPages = parsed.filter(p => p && p.url);
        } catch (_) {}
    }
    renderOtherPages();
    otherSuggestions = [];
    renderOtherSuggestions();
    // Lazily load Shaver suggestions if this offer is linked
    const sugShaverId = offer?.shaver_domain_id || currentShaverDomainId;
    if (sugShaverId) fetchOtherSuggestions(Number(sugShaverId));

    // Traffic tips (list of {label, value})
    trafficTips = [];
    if (offer?.traffic_tips) {
        try {
            const parsed = typeof offer.traffic_tips === 'string' ? JSON.parse(offer.traffic_tips) : offer.traffic_tips;
            if (Array.isArray(parsed)) {
                trafficTips = parsed
                    .map(t => {
                        if (t && typeof t === 'object' && t.label) return { label: t.label, value: String(t.value ?? '') };
                        if (typeof t === 'string' && t.trim() !== '') return { label: 'Note', value: t }; // legacy plain strings
                        return null;
                    })
                    .filter(Boolean);
            }
        } catch (_) {}
    }
    renderTips();

    // Reset prefilled state
    clearAllPrefilled();

    // Mark Shaver-sourced fields as prefilled (yellow)
    if (isFromShaver) {
        if (offer.offer_id) markPrefilled('f_offer_id', 'Auto-filled by Shaver');
        if (offer.platform) markPrefilled('f_platform', 'Auto-filled by Shaver');
        if (links.length > 0) setHint('hint_links', 'Auto-filled by Shaver');
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
        setHint('hint_' + field, 'Auto-filled by Previous Offers');

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
            const existing = links.find(l => l.title === 'Affiliate Page');
            if (existing) {
                existing.url = result.affiliate_url;
            } else {
                links.unshift({ title: 'Affiliate Page', url: result.affiliate_url });
            }
            renderLinks();
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
    links = [];
    trafficTips = [];
    otherPages = [];
    otherSuggestions = [];
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

function adviceTypeFor(advice) {
    const a = (advice || '').toLowerCase().trim();
    if (a === 'prelander' || a === 'pre-lander')        return 'short';
    if (a === 'direct-link' || a === 'direct link')     return 'long';
    if (a === 'vsl')                                    return 'vsl';
    return 'custom';
}

function adviceDescription(advice) {
    const a = (advice || '').toLowerCase().trim();
    if (a === 'prelander' || a === 'pre-lander')        return 'Prelander — best for warming up cold traffic before landing on this page.';
    if (a === 'direct-link' || a === 'direct link')     return 'Direct link — the long copy already does the persuasion itself, no prelander needed.';
    if (a === 'vsl')                                    return 'VSL (Video Sales Letter) — best for viewers who prefer to watch videos over reading.';
    return '';
}

// ============== Other Pages (Shaver-sourced secondary landers) ==============
function renderOtherPages() {
    const box = document.getElementById('otherPagesList');
    if (!box) return;
    if (otherPages.length === 0) {
        box.innerHTML = '';
        return;
    }
    box.innerHTML = '';
    otherPages.forEach((p, i) => {
        const row = document.createElement('div');
        row.className = 'lander-item';
        row.innerHTML = `
            <div class="lander-main">
                <span class="label">${escapeHtml(p.label || 'Page')}</span>
                <span class="url">${escapeHtml(p.url)}</span>
            </div>
            <button type="button" class="remove" onclick="removeOtherPage(${i})" title="Remove">&times;</button>
        `;
        box.appendChild(row);
    });
}

function removeOtherPage(i) {
    otherPages.splice(i, 1);
    renderOtherPages();
}

function renderOtherSuggestions() {
    const box = document.getElementById('otherSuggestionsList');
    if (!box) return;
    if (otherSuggestions.length === 0) {
        box.innerHTML = '<p class="other-sugg-empty">No more suggestions — everything Shaver found is already added or dismissed.</p>';
        return;
    }
    box.innerHTML = '';
    otherSuggestions.forEach((s, i) => {
        const row = document.createElement('div');
        row.className = 'other-sugg-item';
        const visits = s.visits ? `<span class="visits-tag">${s.visits} visits</span>` : '';
        row.innerHTML = `
            <input type="text" class="sugg-title" value="${escapeHtml(s.label || '')}" placeholder="Title" oninput="updateSuggestionTitle(${i}, this.value)">
            <span class="sugg-url">${escapeHtml(s.url)}${visits}</span>
            <button type="button" class="sugg-btn sugg-tick" title="Add to Other Pages" onclick="tickSuggestion(${i})">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
            <button type="button" class="sugg-btn sugg-cross" title="Dismiss" onclick="dismissOtherSuggestion(${i})">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        `;
        box.appendChild(row);
    });
}

function updateSuggestionTitle(i, value) {
    if (!otherSuggestions[i]) return;
    otherSuggestions[i].label = value;
}

function tickSuggestion(i) {
    const s = otherSuggestions[i];
    if (!s || !s.url) return;
    otherPages.push({ label: (s.label || 'Page').trim() || 'Page', url: s.url });
    otherSuggestions.splice(i, 1);
    renderOtherPages();
    renderOtherSuggestions();
}

function dismissOtherSuggestion(i) {
    otherSuggestions.splice(i, 1);
    renderOtherSuggestions();
}

async function fetchOtherSuggestions(domainId) {
    const hint = 'hint_other_pages';
    setHint(hint, 'loading from Shaver…');
    try {
        const res = await fetch('/api.php?action=top_landers', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ domain_id: domainId, limit: 20 }),
        });
        const result = await res.json();
        clearHint(hint);
        if (!result.ok || !Array.isArray(result.landers)) return;

        // Build a set of URLs already in top_landers or other_pages
        const seen = new Set();
        landers.forEach(l => l?.url && seen.add(l.url));
        otherPages.forEach(p => p?.url && seen.add(p.url));

        otherSuggestions = result.landers
            .filter(l => l && l.url && !seen.has(l.url))
            .map(l => ({
                label: l.label || 'Page',
                url: l.url,
                visits: l.visits || 0,
            }));
        renderOtherSuggestions();
        if (otherSuggestions.length > 0) setHint(hint, otherSuggestions.length + ' from Shaver (editable)');
    } catch (_) {
        clearHint(hint);
    }
}

function addTip() {
    const label = document.getElementById('tip_label').value;
    const value = document.getElementById('tip_input').value.trim();
    if (!label || !value) return;
    trafficTips.push({ label, value });
    document.getElementById('tip_input').value = '';
    renderTips();
}
function removeTip(i) { trafficTips.splice(i, 1); renderTips(); }
function moveTip(i, delta) {
    const j = i + delta;
    if (j < 0 || j >= trafficTips.length) return;
    [trafficTips[i], trafficTips[j]] = [trafficTips[j], trafficTips[i]];
    renderTips();
}
function renderTips() {
    const box = document.getElementById('tipsList');
    if (!box) return;
    box.innerHTML = '';
    trafficTips.forEach((t, i) => {
        const row = document.createElement('div');
        row.className = 'lander-item';
        const upDisabled = i === 0 ? 'disabled' : '';
        const downDisabled = i === trafficTips.length - 1 ? 'disabled' : '';
        row.innerHTML = `
            <div class="lander-reorder">
                <button type="button" class="reorder-btn" ${upDisabled} onclick="moveTip(${i}, -1)" title="Move up">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                </button>
                <button type="button" class="reorder-btn" ${downDisabled} onclick="moveTip(${i}, 1)" title="Move down">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
            </div>
            <div class="lander-main">
                <span class="label">${escapeHtml(t.label)}</span>
                <span class="url">${escapeHtml(t.value)}</span>
            </div>
            <button type="button" class="remove" onclick="removeTip(${i})" title="Remove">&times;</button>
        `;
        box.appendChild(row);
    });
}

function addLink() {
    const title = document.getElementById('link_title').value;
    const url = document.getElementById('link_url').value.trim();
    if (!title || !url) return;
    links.push({ title, url });
    document.getElementById('link_url').value = '';
    renderLinks();
}

function removeLink(i) {
    links.splice(i, 1);
    renderLinks();
}

function moveLink(i, delta) {
    const j = i + delta;
    if (j < 0 || j >= links.length) return;
    [links[i], links[j]] = [links[j], links[i]];
    renderLinks();
}

function renderLinks() {
    const box = document.getElementById('linksList');
    if (!box) return;
    box.innerHTML = '';
    links.forEach((l, i) => {
        const row = document.createElement('div');
        row.className = 'lander-item';
        const upDisabled = i === 0 ? 'disabled' : '';
        const downDisabled = i === links.length - 1 ? 'disabled' : '';
        row.innerHTML = `
            <div class="lander-reorder">
                <button type="button" class="reorder-btn" ${upDisabled} onclick="moveLink(${i}, -1)" title="Move up" aria-label="Move up">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                </button>
                <button type="button" class="reorder-btn" ${downDisabled} onclick="moveLink(${i}, 1)" title="Move down" aria-label="Move down">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
            </div>
            <div class="lander-main">
                <span class="label">${escapeHtml(l.title)}</span>
                <span class="url">${escapeHtml(l.url)}</span>
            </div>
            <button type="button" class="remove" onclick="removeLink(${i})" title="Remove">&times;</button>
        `;
        box.appendChild(row);
    });
}

function addLander() {
    const label = document.getElementById('lander_label').value.trim();
    const url = document.getElementById('lander_url').value.trim();
    const adviceEl = document.getElementById('lander_advice');
    const advice = adviceEl ? adviceEl.value.trim() : '';
    if (!label || !url) return;
    const lander = { label, url };
    if (advice) {
        lander.advice = advice;
        lander.type = adviceTypeFor(advice);
    }
    landers.push(lander);
    document.getElementById('lander_label').value = '';
    document.getElementById('lander_url').value = '';
    if (adviceEl) adviceEl.value = '';
    renderLanders();
}

function removeLander(i) {
    if (editingLanderIdx === i) editingLanderIdx = null;
    landers.splice(i, 1);
    renderLanders();
}

function moveLander(i, delta) {
    const j = i + delta;
    if (j < 0 || j >= landers.length) return;
    [landers[i], landers[j]] = [landers[j], landers[i]];
    renderLanders();
}

function editLander(i) {
    editingLanderIdx = i;
    renderLanders();
    setTimeout(() => {
        const el = document.querySelector('.lander-item.editing .edit-label');
        if (el) { el.focus(); el.select(); }
    }, 0);
}

function cancelLanderEdit() {
    editingLanderIdx = null;
    renderLanders();
}

function saveLanderEdit(i) {
    const row = document.querySelector(`.lander-item[data-idx="${i}"]`);
    if (!row) return;
    const label  = row.querySelector('.edit-label').value.trim();
    const url    = row.querySelector('.edit-url').value.trim();
    const advice = row.querySelector('.edit-advice').value.trim();
    if (!label || !url) return;

    const updated = { label, url };
    if (advice) {
        updated.advice = advice;
        updated.type   = adviceTypeFor(advice);
    }
    if (landers[i].visits)    updated.visits    = landers[i].visits;
    if (landers[i].prefilled) updated.prefilled = false;
    landers[i] = updated;
    editingLanderIdx = null;
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
        row.dataset.idx = i;

        if (editingLanderIdx === i) {
            row.className = 'lander-item editing';
            const current = l.advice || '';
            const known = ['prelander', 'direct-link', 'VSL'];
            const hasCustom = current && !known.some(k => k.toLowerCase() === current.toLowerCase());
            const options = [
                `<option value="">— Capsule —</option>`,
                `<option value="prelander"${current.toLowerCase() === 'prelander' ? ' selected' : ''}>prelander</option>`,
                `<option value="direct-link"${current.toLowerCase() === 'direct-link' ? ' selected' : ''}>direct-link</option>`,
                `<option value="VSL"${current.toLowerCase() === 'vsl' ? ' selected' : ''}>VSL</option>`,
                hasCustom ? `<option value="${escapeHtml(current)}" selected>${escapeHtml(current)} (custom)</option>` : '',
            ].join('');
            row.innerHTML = `
                <input type="text" class="edit-label"  value="${escapeHtml(l.label || '')}"  placeholder="Label">
                <input type="url"  class="edit-url"    value="${escapeHtml(l.url || '')}"    placeholder="https://...">
                <select class="edit-advice">${options}</select>
                <button type="button" class="lander-save" onclick="saveLanderEdit(${i})" title="Save">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
                <button type="button" class="lander-cancel" onclick="cancelLanderEdit()" title="Cancel">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            `;
            box.appendChild(row);
            return;
        }

        row.className = 'lander-item' + (l.prefilled ? ' prefilled' : '');
        const derived = deriveLanderType(l.url);
        const hasManual = Boolean(l.advice);
        const label = hasManual ? (l.label || derived.label || 'Lander')
                                : (derived.label || l.label || 'Lander');
        const advice = hasManual ? l.advice : (derived.advice || '');
        const type = hasManual ? (l.type || 'custom')
                               : (derived.type !== 'other' ? derived.type : 'other');
        const description = hasManual
            ? adviceDescription(advice)
            : (derived.description || '');
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
                <span class="label">${adviceChip}${escapeHtml(label)}${visitsBadge}</span>
                <span class="url">${escapeHtml(l.url)}</span>
            </div>
            <button type="button" class="lander-edit" onclick="editLander(${i})" title="Edit">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </button>
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
        links: links.map(({ title, url }) => ({ title, url })),
        traffic_tips: trafficTips.map(({ label, value }) => ({ label, value })),
        other_pages: otherPages.map(({ label, url }) => ({ label, url })),
        top_landers: landers.map(l => {
            const out = { label: l.label, url: l.url };
            if (l.advice) out.advice = l.advice;
            if (l.type)   out.type   = l.type;
            return out;
        }),
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
        // Clamp within viewport
        const tipRect = tip.getBoundingClientRect();
        const vw = window.innerWidth;
        const margin = 10;
        let dx = 0;
        if (tipRect.right > vw - margin) dx = tipRect.right - (vw - margin);
        else if (tipRect.left < margin) dx = tipRect.left - margin;
        if (dx !== 0) tip.style.left = (parseFloat(tip.style.left) - dx) + 'px';
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
