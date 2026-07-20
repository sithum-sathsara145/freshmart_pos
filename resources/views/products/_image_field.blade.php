{{-- products/_image_field.blade.php — pick → edit (crop/zoom/rotate) → confirm →
     upload straight to Cloudinary (async, with progress). The product form then just
     stores the resulting URL. If the direct upload fails, the file falls back to a
     server-side upload when the product is saved. --}}
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">
@endpush

@php $hasImg = ($product ?? null) && $product->imageUrl(); @endphp
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:12px">Product image</div>

    {{-- Preview: existing image, or the just-confirmed crop (shown via JS) --}}
    <div id="img-preview-wrap" style="{{ $hasImg ? '' : 'display:none' }};position:relative;margin-bottom:8px">
        <img id="img-preview" src="{{ $hasImg ? $product->imageUrl() : '' }}"
             style="width:100%;height:160px;object-fit:cover;border-radius:8px;background:var(--bg)">
        <button type="button" id="img-remove" title="Remove image"
            style="position:absolute;top:6px;right:6px;width:26px;height:26px;background:var(--overlay);border:.5px solid var(--border);border-radius:6px;color:var(--danger);cursor:pointer;display:flex;align-items:center;justify-content:center">
            <i class="ti ti-trash" style="font-size:14px"></i>
        </button>
        {{-- Upload progress overlay --}}
        <div id="img-progress" style="display:none;position:absolute;inset:0;background:var(--overlay);border-radius:8px;flex-direction:column;align-items:center;justify-content:center;gap:8px">
            <div style="width:78%;height:6px;background:var(--border);border-radius:3px;overflow:hidden">
                <div id="img-progress-bar" style="height:100%;width:0;background:var(--success);transition:width .15s"></div>
            </div>
            <div id="img-progress-text" style="color:var(--text-hi);font-size:11px">Uploading 0%</div>
        </div>
    </div>

    {{-- Upload status line --}}
    <div id="img-status" style="display:none;align-items:center;gap:5px;font-size:11px;margin-bottom:8px"></div>

    <label id="img-drop" for="image-input"
        style="width:100%;height:{{ $hasImg ? '42px' : '160px' }};background:var(--bg);border:.5px dashed var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;gap:6px;color:var(--text-3);font-size:12px">
        <i class="ti ti-upload" style="font-size:16px"></i><span id="img-drop-label">{{ $hasImg ? 'Change image' : 'Click to upload image' }}</span>
    </label>

    {{-- image_url/public_id hold a completed direct upload; the file input is the
         server-side fallback; remove_image clears an existing image. --}}
    <input type="file" name="image" id="image-input" accept="image/*" style="display:none">
    <input type="hidden" name="image_url" id="image-url" value="">
    <input type="hidden" name="image_public_id" id="image-public-id" value="">
    <input type="hidden" name="remove_image" id="remove-image" value="0">

    @error('image')<div style="color:var(--danger);font-size:10px;margin-top:6px">{{ $message }}</div>@enderror
</div>

{{-- ── Editor modal ─────────────────────────────────────────────────── --}}
<div id="img-editor" style="display:none;position:fixed;inset:0;z-index:9999;background:var(--overlay);align-items:center;justify-content:center;padding:20px">
    <div style="background:var(--sunken);border:.5px solid var(--border);border-radius:12px;width:100%;max-width:620px;max-height:92vh;display:flex;flex-direction:column;overflow:hidden">
        <div style="padding:13px 16px;border-bottom:.5px solid var(--border);display:flex;align-items:center;gap:8px">
            <div style="font-size:13px;font-weight:600;color:var(--text);flex:1">Edit image</div>
            <button type="button" id="ed-cancel-x" style="background:none;border:none;color:var(--text-3);cursor:pointer;font-size:18px;line-height:1"><i class="ti ti-x"></i></button>
        </div>

        <div style="padding:14px;background:var(--sunken-2);flex:1;min-height:0;overflow:auto">
            <div style="max-height:54vh"><img id="cropper-img" style="max-width:100%;display:block"></div>
        </div>

        {{-- Toolbar --}}
        <div style="padding:10px 14px;border-top:.5px solid var(--border);display:flex;flex-wrap:wrap;gap:6px;align-items:center">
            @php $btn = 'height:30px;min-width:30px;padding:0 9px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-hi);font-size:11px;cursor:pointer;display:inline-flex;align-items:center;gap:4px'; @endphp
            <button type="button" id="ed-ratio-sq"   style="{{ $btn }}" title="Square crop"><i class="ti ti-square"></i>1:1</button>
            <button type="button" id="ed-ratio-free" style="{{ $btn }}" title="Free crop"><i class="ti ti-crop"></i>Free</button>
            <span style="width:1px;height:18px;background:var(--border);margin:0 2px"></span>
            <button type="button" id="ed-zoom-in"  style="{{ $btn }}" title="Zoom in"><i class="ti ti-zoom-in"></i></button>
            <button type="button" id="ed-zoom-out" style="{{ $btn }}" title="Zoom out"><i class="ti ti-zoom-out"></i></button>
            <button type="button" id="ed-rot-l" style="{{ $btn }}" title="Rotate left"><i class="ti ti-rotate-2"></i></button>
            <button type="button" id="ed-rot-r" style="{{ $btn }}" title="Rotate right"><i class="ti ti-rotate-clockwise-2"></i></button>
            <button type="button" id="ed-flip"  style="{{ $btn }}" title="Flip horizontal"><i class="ti ti-flip-horizontal"></i></button>
            <button type="button" id="ed-reset" style="{{ $btn }}" title="Reset"><i class="ti ti-refresh"></i></button>
        </div>

        <div style="padding:12px 16px;border-top:.5px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:8px">
            <div style="font-size:10px;color:var(--text-3)">Drag to move · scroll to zoom · uploaded as compressed WebP</div>
            <div style="display:flex;gap:8px">
                <button type="button" id="ed-cancel" style="height:34px;padding:0 16px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Cancel</button>
                <button type="button" id="ed-confirm" style="height:34px;padding:0 18px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:600;cursor:pointer"><i class="ti ti-check" style="margin-right:4px"></i>Confirm</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Saving overlay (shown while the product form submits) ──────────── --}}
<div id="img-uploading" style="display:none;position:fixed;inset:0;z-index:10000;background:var(--overlay);align-items:center;justify-content:center;flex-direction:column;gap:14px">
    <div style="width:38px;height:38px;border:3px solid var(--border);border-top-color:var(--success);border-radius:50%;animation:imgspin .8s linear infinite"></div>
    <div style="color:var(--text-hi);font-size:13px">Saving… please wait</div>
</div>
<style>@keyframes imgspin{to{transform:rotate(360deg)}}</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
(function () {
    const MAX = 1000, QUALITY = 0.85;
    const SIGN_URL = @json(route('products.upload-signature'));
    const PRODUCTS_URL = @json(route('products.index'));

    const fileInput   = document.getElementById('image-input');
    const imageUrlIn  = document.getElementById('image-url');
    const publicIdIn  = document.getElementById('image-public-id');
    const removeFlag  = document.getElementById('remove-image');
    const drop        = document.getElementById('img-drop');
    const dropLabel   = document.getElementById('img-drop-label');
    const previewWrap = document.getElementById('img-preview-wrap');
    const preview     = document.getElementById('img-preview');
    const removeBtn   = document.getElementById('img-remove');
    const editor      = document.getElementById('img-editor');
    const cropImg     = document.getElementById('cropper-img');
    const progress    = document.getElementById('img-progress');
    const progressBar = document.getElementById('img-progress-bar');
    const progressTxt = document.getElementById('img-progress-text');
    const statusLine  = document.getElementById('img-status');
    const overlay     = document.getElementById('img-uploading');
    const form        = fileInput.closest('form');
    let cropper = null, srcUrl = null, previewUrl = null, uploading = false;

    const revoke = u => { try { if (u) URL.revokeObjectURL(u); } catch (e) {} };

    function showPreview(url) {
        revoke(previewUrl); previewUrl = url;
        preview.src = url;
        previewWrap.style.display = '';
        drop.style.height = '42px';
        dropLabel.textContent = 'Change image';
    }
    function clearPreview() {
        revoke(previewUrl); previewUrl = null;
        previewWrap.style.display = 'none';
        preview.removeAttribute('src');
        drop.style.height = '160px';
        dropLabel.textContent = 'Click to upload image';
        setStatus('none');
    }

    function setStatus(state, pct) {
        progress.style.display = state === 'uploading' ? 'flex' : 'none';
        if (state === 'uploading') {
            progressBar.style.width = (pct || 0) + '%';
            progressTxt.textContent = 'Uploading ' + (pct || 0) + '%';
        }
        if (state === 'done') {
            statusLine.style.display = 'flex'; statusLine.style.color = 'var(--success)';
            statusLine.innerHTML = '<i class="ti ti-cloud-check" style="font-size:13px"></i> Image uploaded';
        } else if (state === 'pending') {
            statusLine.style.display = 'flex'; statusLine.style.color = 'var(--warning-2)';
            statusLine.innerHTML = '<i class="ti ti-clock" style="font-size:13px"></i> Will upload when you save';
        } else if (state !== 'uploading') {
            statusLine.style.display = 'none';
        }
    }

    // ── React-style async direct upload to Cloudinary ──
    async function getSignature() {
        const res = await fetch(SIGN_URL, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'Could not start the upload.');
        return data;
    }
    function cloudinaryUpload(blob, sig, onProgress) {
        return new Promise((resolve, reject) => {
            const fd = new FormData();
            fd.append('file', blob, 'image.webp');
            fd.append('api_key', sig.api_key);
            fd.append('timestamp', sig.timestamp);
            fd.append('signature', sig.signature);
            fd.append('folder', sig.folder);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'https://api.cloudinary.com/v1_1/' + sig.cloud_name + '/image/upload');
            xhr.upload.onprogress = e => { if (e.lengthComputable && onProgress) onProgress(Math.round(e.loaded / e.total * 100)); };
            xhr.onload = () => {
                let data = {};
                try { data = JSON.parse(xhr.responseText); } catch (e) {}
                if (xhr.status >= 200 && xhr.status < 300 && data.secure_url) {
                    resolve({ url: data.secure_url, public_id: data.public_id });
                } else {
                    reject(new Error((data.error && data.error.message) || 'Cloudinary did not accept the image.'));
                }
            };
            xhr.onerror = () => reject(new Error('Network error while uploading to Cloudinary.'));
            xhr.send(fd);
        });
    }

    // Show preview, upload to Cloudinary; on failure keep the blob for a server fallback.
    async function processBlob(blob) {
        clearError();
        removeFlag.value = '0';
        imageUrlIn.value = '';
        publicIdIn.value = '';
        fileInput.value = '';
        showPreview(URL.createObjectURL(blob));
        uploading = true;
        setStatus('uploading', 0);
        try {
            const sig = await getSignature();
            const { url, public_id } = await cloudinaryUpload(blob, sig, p => setStatus('uploading', p));
            imageUrlIn.value = url;
            publicIdIn.value = public_id;
            setStatus('done');
        } catch (err) {
            // Fallback: server uploads the file when the product is saved.
            const dt = new DataTransfer();
            dt.items.add(new File([blob], 'image.webp', { type: 'image/webp' }));
            fileInput.files = dt.files;
            setStatus('pending');
            showError(err.message + ' It will be uploaded when you save the product.');
        } finally {
            uploading = false;
        }
    }

    // ── Fallback when Cropper can't load: resize + compress, no editor UI ──
    function compressDirect(file) {
        const img = new Image();
        const url = URL.createObjectURL(file);
        img.onload = function () {
            let { width: w, height: h } = img;
            if (w > MAX || h > MAX) { const r = Math.min(MAX / w, MAX / h); w = Math.round(w * r); h = Math.round(h * r); }
            const c = document.createElement('canvas'); c.width = w; c.height = h;
            c.getContext('2d').drawImage(img, 0, 0, w, h);
            revoke(url);
            c.toBlob(b => { if (b) processBlob(b); }, 'image/webp', QUALITY);
        };
        img.onerror = function () { revoke(url); };
        img.src = url;
    }

    function openEditor(file) {
        revoke(srcUrl); srcUrl = URL.createObjectURL(file);
        cropImg.src = srcUrl;
        editor.style.display = 'flex';
        if (cropper) cropper.destroy();
        cropper = new Cropper(cropImg, { viewMode: 1, autoCropArea: 1, aspectRatio: 1, background: false, responsive: true });
    }
    function closeEditor() {
        editor.style.display = 'none';
        if (cropper) { cropper.destroy(); cropper = null; }
        revoke(srcUrl); srcUrl = null;
        fileInput.value = '';   // allow re-picking the same file
    }

    // ── File picked ──
    fileInput.addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) return;
        if (typeof Cropper === 'undefined') { compressDirect(file); fileInput.value = ''; return; }
        openEditor(file);
    });

    // ── Toolbar ──
    const on = (id, fn) => { const el = document.getElementById(id); if (el) el.addEventListener('click', fn); };
    on('ed-ratio-sq',  () => cropper && cropper.setAspectRatio(1));
    on('ed-ratio-free',() => cropper && cropper.setAspectRatio(NaN));
    on('ed-zoom-in',   () => cropper && cropper.zoom(0.1));
    on('ed-zoom-out',  () => cropper && cropper.zoom(-0.1));
    on('ed-rot-l',     () => cropper && cropper.rotate(-90));
    on('ed-rot-r',     () => cropper && cropper.rotate(90));
    let flipped = 1;
    on('ed-flip',      () => { if (cropper) { flipped = -flipped; cropper.scaleX(flipped); } });
    on('ed-reset',     () => { if (cropper) { cropper.reset(); flipped = 1; } });
    on('ed-cancel',    closeEditor);
    on('ed-cancel-x',  closeEditor);

    // ── Confirm: crop + compress, then upload ──
    on('ed-confirm', () => {
        if (!cropper) return;
        const canvas = cropper.getCroppedCanvas({ maxWidth: MAX, maxHeight: MAX, imageSmoothingQuality: 'high' });
        if (!canvas) { closeEditor(); return; }
        canvas.toBlob(blob => { closeEditor(); if (blob) processBlob(blob); }, 'image/webp', QUALITY);
    });

    // ── Remove current image ──
    removeBtn.addEventListener('click', () => {
        fileInput.value = '';
        imageUrlIn.value = '';
        publicIdIn.value = '';
        removeFlag.value = '1';
        clearPreview();
    });

    // ── Inline error banner (no browser alerts) ──
    let errBox = null;
    function ensureErrBox() {
        if (errBox) return errBox;
        errBox = document.createElement('div');
        errBox.style.cssText = 'background:var(--danger-soft);color:var(--danger-text);border:.5px solid var(--danger-border);border-radius:7px;padding:10px 14px;margin-bottom:12px;font-size:12px;display:none';
        form.prepend(errBox);
        return errBox;
    }
    function showError(msg) {
        const box = ensureErrBox(); box.innerHTML = '';
        const items = Array.isArray(msg) ? msg : [msg];
        const head = document.createElement('div');
        head.style.cssText = 'display:flex;align-items:center;gap:8px;font-weight:500';
        head.innerHTML = '<i class="ti ti-alert-triangle" style="font-size:15px"></i>';
        const span = document.createElement('span');
        span.textContent = items.length > 1 ? 'Please fix the following:' : items[0];
        head.appendChild(span); box.appendChild(head);
        if (items.length > 1) {
            const ul = document.createElement('ul');
            ul.style.cssText = 'margin:6px 0 0;padding-left:24px';
            items.forEach(t => { const li = document.createElement('li'); li.textContent = t; ul.appendChild(li); });
            box.appendChild(ul);
        }
        box.style.display = 'block';
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    function clearError() { if (errBox) errBox.style.display = 'none'; }

    function busy(btn, on) {
        overlay.style.display = on ? 'flex' : 'none';
        if (btn) { btn.disabled = on; btn.style.opacity = on ? '.6' : ''; btn.style.cursor = on ? 'wait' : ''; }
    }

    // ── Submit via AJAX: stay on the screen, show progress, show errors inline ──
    form.addEventListener('submit', async e => {
        e.preventDefault();
        clearError();
        if (uploading) { showError('Please wait for the image to finish uploading.'); return; }
        const btn = form.querySelector('button[type="submit"]');
        busy(btn, true);
        let navigating = false;
        try {
            const res = await fetch(form.action, {
                method: 'POST',                       // _method=PUT in body handles edit
                body: new FormData(form),
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
                navigating = true;
                window.location = data.redirect || PRODUCTS_URL;
                return;
            }
            if (res.status === 422 && data.errors) {
                showError(Object.values(data.errors).flat());
            } else {
                showError(data.message || 'Something went wrong. Please try again.');
            }
        } catch (err) {
            showError('Network error — please check your connection and try again.');
        } finally {
            if (!navigating) busy(btn, false);
        }
    });
})();
</script>
@endpush
