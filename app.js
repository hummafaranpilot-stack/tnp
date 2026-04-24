let landers = [];
let editingId = null;

function getNextSr() {
    const el = document.querySelector('main[data-next-sr]');
    return el ? Number(el.dataset.nextSr) || 1 : 1;
}

function openForm(offer = null) {
    editingId = offer ? offer.id : null;
    document.getElementById('modalTitle').textContent = offer ? 'Edit Offer' : 'Add New Offer';
    document.getElementById('f_id').value = offer?.id ?? '';
    document.getElementById('f_sr').value = offer?.sr ?? getNextSr();
    document.getElementById('f_platform').value = offer?.platform ?? 'BuyGoods';
    document.getElementById('f_offer_name').value = offer?.offer_name ?? '';
    document.getElementById('f_offer_id').value = offer?.offer_id ?? '';
    document.getElementById('f_category').value = offer?.category ?? 'Weight Loss';
    document.getElementById('f_revshare').value = offer?.revshare ?? '';
    document.getElementById('f_cpa').value = offer?.cpa ?? '';
    document.getElementById('f_allowed_geos').value = offer?.allowed_geos ?? 'Tier-1';
    document.getElementById('f_restriction').value = offer?.restriction ?? 'No';
    document.getElementById('f_affiliate_page_url').value = offer?.affiliate_page_url ?? '';

    // Image
    const imgUrl = offer?.image_url ?? '';
    document.getElementById('f_image_url').value = imgUrl;
    document.getElementById('f_image_file').value = '';
    document.getElementById('uploadStatus').textContent = '';
    switchImageTab('url');
    if (imgUrl) {
        showPreview(imgUrl);
    } else {
        hidePreview();
    }

    landers = [];
    if (offer?.top_landers) {
        try {
            const parsed = typeof offer.top_landers === 'string'
                ? JSON.parse(offer.top_landers)
                : offer.top_landers;
            if (Array.isArray(parsed)) landers = parsed;
        } catch (_) {}
    }
    renderLanders();

    document.getElementById('modal').classList.remove('hidden');
}

function closeForm() {
    document.getElementById('modal').classList.add('hidden');
    editingId = null;
    landers = [];
}

function switchImageTab(which) {
    document.querySelectorAll('.image-tabs .tab').forEach(t =>
        t.classList.toggle('active', t.dataset.tab === which));
    document.getElementById('imageUrlPane').classList.toggle('hidden', which !== 'url');
    document.getElementById('imageUploadPane').classList.toggle('hidden', which !== 'upload');
}

function previewImage(url) {
    if (url && url.trim()) {
        showPreview(url.trim());
    } else {
        hidePreview();
    }
}

function showPreview(url) {
    const box = document.getElementById('imagePreview');
    const img = document.getElementById('previewImg');
    img.src = url;
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
        row.className = 'lander-item';
        row.innerHTML = `
            <span class="label">${escapeHtml(l.label)}</span>
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
        top_landers: landers,
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
    if (result.ok) {
        window.location.reload();
    } else {
        alert('Delete failed: ' + (result.error || 'Unknown error'));
    }
}
