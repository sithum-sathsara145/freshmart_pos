{{-- products/_image_field.blade.php — upload with crop + resize + WebP compress (client-side) --}}
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">
@endpush

@php $hasImg = ($product ?? null) && $product->imageUrl(); @endphp
<div style="background:#161821;border:.5px solid #2a2d3a;border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:#94a3b8;margin-bottom:12px">Product image</div>

    @if($hasImg)
    <img id="current-img" src="{{ $product->imageUrl() }}" style="width:100%;height:140px;object-fit:cover;border-radius:8px;margin-bottom:8px">
    @endif

    <label id="img-drop" for="image-input" style="width:100%;height:{{ $hasImg ? '46px' : '140px' }};background:#0f1117;border:.5px dashed #2a2d3a;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;gap:6px;color:#64748b;font-size:12px">
        <i class="ti ti-upload" style="font-size:16px"></i>{{ $hasImg ? 'Change image' : 'Click to upload image' }}
    </label>
    <input type="file" name="image" id="image-input" accept="image/*" style="display:none">

    <div id="cropper-wrap" style="display:none;margin-top:8px">
        <img id="cropper-img" style="max-width:100%;display:block">
        <div style="font-size:10px;color:#64748b;margin-top:6px">Drag to reposition · scroll to zoom · saved as compressed WebP</div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
(function () {
    const fileInput = document.getElementById('image-input');
    const cropImg   = document.getElementById('cropper-img');
    const wrap      = document.getElementById('cropper-wrap');
    const form      = fileInput.closest('form');
    let cropper = null;

    fileInput.addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) return;
        cropImg.src = URL.createObjectURL(file);
        wrap.style.display = 'block';
        if (cropper) cropper.destroy();
        cropper = new Cropper(cropImg, { viewMode: 1, autoCropArea: 1, background: false, responsive: true });
    });

    // On submit, replace the raw file with a cropped, resized, compressed WebP
    form.addEventListener('submit', function (e) {
        if (!cropper) return;                 // no new image — submit normally
        e.preventDefault();
        const canvas = cropper.getCroppedCanvas({ maxWidth: 1000, maxHeight: 1000, imageSmoothingQuality: 'high' });
        if (!canvas) { cropper = null; form.submit(); return; }
        canvas.toBlob(blob => {
            if (blob) {
                const dt = new DataTransfer();
                dt.items.add(new File([blob], 'image.webp', { type: 'image/webp' }));
                fileInput.files = dt.files;
            }
            cropper = null;                   // avoid re-intercepting the resubmit
            form.submit();
        }, 'image/webp', 0.8);
    });
})();
</script>
@endpush
