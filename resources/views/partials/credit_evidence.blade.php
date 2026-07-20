{{-- Reusable "attach signed copy" widget for back-office pages.
     Wrap the page content in x-data="creditEvidence()" and drop this partial inside it;
     trigger from any button with @click="start(saleId, invoiceNo)". --}}

<template x-teleport="body">
<div x-show="evOpen" x-cloak @keydown.escape.window="evClose()"
     style="position:fixed;inset:0;background:rgba(8,9,13,.72);display:flex;align-items:center;justify-content:center;z-index:80">
    <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:12px;padding:18px;width:400px;max-height:94vh;overflow-y:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
            <div style="font-size:14px;font-weight:600;color:#e2e8f0;display:flex;align-items:center;gap:6px"><i class="ti ti-signature" style="color:#818cf8"></i> Signed copy</div>
            <i class="ti ti-x" @click="evClose()" style="font-size:16px;color:#64748b;cursor:pointer"></i>
        </div>
        <div style="font-size:11px;color:#64748b;margin-bottom:12px" x-text="evInvoice ? 'Invoice ' + evInvoice + ' · photograph the signed bill' : ''"></div>

        <template x-if="evDone">
            <div style="text-align:center;padding:18px 0">
                <div style="width:52px;height:52px;border-radius:50%;background:#14532d;display:flex;align-items:center;justify-content:center;margin:0 auto 12px"><i class="ti ti-check" style="font-size:28px;color:#4ade80"></i></div>
                <div style="font-size:14px;font-weight:600;color:#e2e8f0;margin-bottom:4px">Signed copy attached</div>
                <div style="font-size:12px;color:#64748b;margin-bottom:16px">Saved as evidence for this credit sale.</div>
                <button @click="evClose()" style="width:100%;height:38px;background:#312e81;border:.5px solid #534AB7;border-radius:7px;color:#a5b4fc;font-size:13px;font-weight:600;cursor:pointer">Done</button>
            </div>
        </template>

        <template x-if="!evDone">
          <div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:12px">
                <button @click="evTabTo('webcam')" :style="evTab==='webcam' ? 'background:#312e81;color:#a5b4fc;border-color:#534AB7' : 'background:#1e2130;color:#94a3b8;border-color:#2a2d3a'" style="height:34px;border:.5px solid;border-radius:7px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-camera" style="font-size:13px"></i> Webcam</button>
                <button @click="evTabTo('phone')" :style="evTab==='phone' ? 'background:#312e81;color:#a5b4fc;border-color:#534AB7' : 'background:#1e2130;color:#94a3b8;border-color:#2a2d3a'" style="height:34px;border:.5px solid;border-radius:7px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-device-mobile" style="font-size:13px"></i> Use my phone</button>
            </div>

            <div x-show="evTab==='webcam'">
                <video x-ref="evVideo" autoplay playsinline muted style="width:100%;border-radius:8px;background:#0f1117;aspect-ratio:4/3;object-fit:cover"></video>
                <button @click="evCapture()" :disabled="evBusy" style="width:100%;height:40px;margin-top:10px;background:#14532d;border:.5px solid #166534;border-radius:7px;color:#4ade80;font-size:13px;font-weight:600;cursor:pointer"><i class="ti ti-camera" style="font-size:14px"></i> <span x-text="evBusy ? 'Uploading…' : 'Capture & upload'"></span></button>
            </div>

            <div x-show="evTab==='phone'" style="text-align:center">
                <div style="font-size:12px;color:#94a3b8;margin-bottom:10px">Scan with your phone, then enter the code on the phone.</div>
                <div style="background:#fff;border-radius:8px;padding:10px;display:inline-block;min-width:180px;min-height:180px" x-html="evQr"></div>
                <div x-show="evQrLoading" x-cloak style="font-size:11px;color:#64748b;margin-top:8px">Creating link…</div>
                <div x-show="evCode" x-cloak style="margin-top:12px">
                    <div style="font-size:11px;color:#64748b">Security code</div>
                    <div style="font-size:26px;font-weight:700;letter-spacing:6px;color:#e2e8f0" x-text="evCode"></div>
                    <div style="font-size:10px;color:#475569;margin-top:2px">Or enter your own login password on the phone.</div>
                </div>
                <div style="font-size:11px;color:#a5b4fc;margin-top:12px"><i class="ti ti-loader-2" style="font-size:12px"></i> Waiting for the phone upload…</div>
            </div>

            <div x-show="evMsg" x-cloak style="font-size:11px;color:#f87171;margin-top:10px;text-align:center" x-text="evMsg"></div>
            <button @click="evClose()" style="width:100%;height:34px;margin-top:12px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;cursor:pointer">Cancel</button>
          </div>
        </template>
    </div>
</div>
</template>

@push('scripts')
<script>
function creditEvidence() {
    return {
        evOpen: false, evSaleId: null, evInvoice: '', evTab: 'webcam',
        evStream: null, evBusy: false, evMsg: '', evDone: false,
        evQr: '', evCode: '', evQrLoading: false, evPoll: null,
        evCsrf() { return document.querySelector('meta[name=csrf-token]').content; },

        start(saleId, invoice) {
            this.evSaleId = saleId; this.evInvoice = invoice || '';
            this.evTab = 'webcam'; this.evMsg = ''; this.evDone = false; this.evQr = ''; this.evCode = '';
            this.evOpen = true;
            this.$nextTick(() => this.evStartWebcam());
        },
        async evStartWebcam() {
            this.evStopWebcam(); this.evMsg = '';
            try {
                this.evStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                const v = this.$refs.evVideo;
                if (v) { v.srcObject = this.evStream; await v.play().catch(() => {}); }
            } catch (e) { this.evMsg = 'No webcam available — use your phone instead.'; this.evTabTo('phone'); }
        },
        evStopWebcam() { if (this.evStream) { this.evStream.getTracks().forEach(t => t.stop()); this.evStream = null; } },
        async evCapture() {
            const v = this.$refs.evVideo;
            if (!v || !v.videoWidth) { this.evMsg = 'Camera not ready yet.'; return; }
            this.evBusy = true; this.evMsg = 'Uploading…';
            try {
                const c = document.createElement('canvas'); c.width = v.videoWidth; c.height = v.videoHeight;
                c.getContext('2d').drawImage(v, 0, 0);
                const blob = await new Promise(ok => c.toBlob(ok, 'image/webp', 0.85));
                const fd = new FormData();
                fd.append('photo', blob, 'signed.webp'); fd.append('_token', this.evCsrf());
                const res = await fetch(`/pos/sale/${this.evSaleId}/credit-document`, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.evCsrf() }, body: fd });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) { this.evDone = true; this.evStopWebcam(); this.evStopPoll(); }
                else { this.evMsg = data.message || 'Upload failed. Try again.'; }
            } catch (e) { this.evMsg = 'Upload failed. Try again.'; }
            this.evBusy = false;
        },
        evTabTo(tab) {
            this.evTab = tab; this.evMsg = '';
            if (tab === 'webcam') { this.evStopPoll(); this.evStartWebcam(); }
            else { this.evStopWebcam(); this.evLoadQr(); this.evStartPoll(); }
        },
        async evLoadQr() {
            this.evQrLoading = true; this.evQr = ''; this.evCode = '';
            try {
                const res = await fetch(`/pos/sale/${this.evSaleId}/credit-upload-link`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.evQr = data.qr_svg || ''; this.evCode = data.code || '';
            } catch (e) { this.evMsg = 'Could not create the upload link.'; }
            this.evQrLoading = false;
        },
        evStartPoll() {
            this.evStopPoll();
            this.evPoll = setInterval(async () => {
                if (!this.evSaleId || this.evDone) return;
                try {
                    const res = await fetch(`/pos/sale/${this.evSaleId}/credit-document`, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (data.attached) { this.evDone = true; this.evStopPoll(); this.evStopWebcam(); }
                } catch (e) {}
            }, 3000);
        },
        evStopPoll() { if (this.evPoll) { clearInterval(this.evPoll); this.evPoll = null; } },
        evClose() { this.evStopWebcam(); this.evStopPoll(); this.evOpen = false; if (this.evDone) window.location.reload(); },
    };
}
</script>
@endpush
