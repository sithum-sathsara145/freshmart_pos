{{-- stock/conversions.blade.php — breaking bulk packs into retail stock --}}
@extends('layouts.app')
@section('title','Bulk Breaking')
@section('page-title','Bulk Breaking')
@section('content')
@php
    $inp  = 'background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:7px 9px;outline:none;box-sizing:border-box';
    $trim = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.');
@endphp
<div style="padding:14px 16px" x-data="bulkBreak()">

<div style="font-size:11px;color:var(--text-3);margin-bottom:12px;max-width:760px;line-height:1.6">
    Open a bulk pack and turn it into the retail item you sell it as — a 20kg bag of sugar
    into loose sugar, or into 500g packets. What the bag cost follows the stock across, so
    the retail profit is measured against what you really paid, and a retail customer can
    never be charged the wholesale rate.
</div>

<div style="display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:12px;align-items:start">

{{-- ── Break something ─────────────────────────────────────────── --}}
<div>
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px;margin-bottom:12px">
        <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:10px">Break a bulk pack</div>

        @if($rules->isEmpty())
        <div style="font-size:12px;color:var(--text-3);padding:10px 0">
            No breakdowns set up yet. Add one on the right — say which bulk product becomes
            which retail product, and how much one pack gives.
        </div>
        @else
        <form method="POST" action="{{ route('stock.conversions.store') }}">
        @csrf
        <div style="margin-bottom:10px">
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">What are you breaking?</label>
            <select name="conversion_id" x-model="ruleId" @change="syncRule()" required style="{{ $inp }};width:100%;height:34px">
                <option value="">— pick a breakdown —</option>
                @foreach($rules as $r)
                <option value="{{ $r->id }}"
                        data-yield="{{ (float) $r->yield_qty }}"
                        data-fixed="{{ $r->yieldIsFixed() ? 1 : 0 }}"
                        data-unit="{{ $r->to?->unit }}"
                        data-from-unit="{{ $r->from?->unit }}"
                        data-stock="{{ (float) ($onHand[$r->from_product_id] ?? 0) }}"
                        data-retail="{{ (float) ($retailOnHand[$r->to_product_id] ?? 0) }}">
                    {{ $r->from?->name }} → {{ $r->to?->name }} ({{ $r->label() }})
                </option>
                @endforeach
            </select>
        </div>

        <template x-if="rule">
            <div>
                <div style="display:flex;gap:14px;flex-wrap:wrap;background:var(--bg);border:.5px solid var(--border);border-radius:7px;padding:9px 11px;margin-bottom:10px;font-size:11px">
                    <div><span style="color:var(--text-3)">Bulk in stock</span>
                        <b :style="rule.stock > 0 ? 'color:var(--text)' : 'color:var(--danger)'"
                           x-text="' ' + fmt(rule.stock) + ' ' + rule.fromUnit"></b></div>
                    <div><span style="color:var(--text-3)">Retail in stock</span>
                        <b :style="rule.retail > 0 ? 'color:var(--text)' : 'color:var(--warning-2)'"
                           x-text="' ' + fmt(rule.retail) + ' ' + rule.unit"></b></div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
                    <div>
                        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">
                            How many to break (<span x-text="rule.fromUnit"></span>)
                        </label>
                        <input type="number" name="from_qty" x-model="fromQty" step="0.001" min="0.001" required
                               style="{{ $inp }};width:100%;height:34px">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">
                            You get (<span x-text="rule.unit"></span>)
                        </label>
                        {{-- Weight can't go missing between a bag and a bin, so that yield
                             is fixed. Counted packets are where spillage shows up. --}}
                        <template x-if="rule.fixed">
                            <input type="text" :value="fmt(expected)" readonly
                                   style="{{ $inp }};width:100%;height:34px;color:var(--text-3);cursor:not-allowed"
                                   title="Weighed item — the yield is fixed">
                        </template>
                        <template x-if="!rule.fixed">
                            <input type="number" name="to_qty" x-model="toQty" step="0.001" min="0.001"
                                   style="{{ $inp }};width:100%;height:34px">
                        </template>
                    </div>
                </div>

                <div x-show="rule.fixed" x-cloak style="font-size:10.5px;color:var(--text-4);margin-bottom:10px">
                    Sold by weight, so a pack always gives its full amount.
                </div>
                <div x-show="!rule.fixed" x-cloak style="font-size:10.5px;color:var(--text-4);margin-bottom:10px">
                    Usually <b x-text="fmt(expected) + ' ' + rule.unit"></b> — change it if you got fewer,
                    or leave it if you'd rather not track the difference.
                </div>

                <div x-show="shortfall > 0" x-cloak
                     style="font-size:11px;background:var(--warning-soft-3);border:.5px solid var(--warning-border-2);border-radius:6px;padding:7px 9px;margin-bottom:10px;color:var(--warning-2)">
                    <span x-text="fmt(shortfall) + ' ' + rule.unit"></span> short of the usual yield —
                    recorded as spillage, and the pack's cost spreads over what you actually got.
                </div>
                <div x-show="overYield" x-cloak
                     style="font-size:11px;background:var(--danger-soft);border:.5px solid var(--danger-border);border-radius:6px;padding:7px 9px;margin-bottom:10px;color:var(--danger)">
                    That's more than the pack holds — a break can't create stock.
                </div>
                <div x-show="rule.stock > 0 && fromValue > rule.stock" x-cloak
                     style="font-size:11px;background:var(--danger-soft);border:.5px solid var(--danger-border);border-radius:6px;padding:7px 9px;margin-bottom:10px;color:var(--danger)">
                    Only <span x-text="fmt(rule.stock) + ' ' + rule.fromUnit"></span> in stock here.
                </div>

                <input type="text" name="note" placeholder="Note (optional)" style="{{ $inp }};width:100%;height:34px;margin-bottom:10px">

                <button type="submit" :disabled="overYield || fromValue > rule.stock || fromValue <= 0"
                        :style="(overYield || fromValue > rule.stock || fromValue <= 0) ? 'opacity:.5;cursor:not-allowed' : ''"
                        style="height:36px;padding:0 18px;background:var(--success-soft);border:.5px solid var(--success-border);border-radius:6px;color:var(--success);font-size:12px;font-weight:600;cursor:pointer">
                    <i class="ti ti-scissors" style="font-size:13px;margin-right:4px"></i>Break it
                </button>
            </div>
        </template>
        </form>
        @endif
    </div>

    {{-- ── What's been broken ──────────────────────────────────── --}}
    <div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;overflow:hidden">
        <div style="font-size:12px;font-weight:500;color:var(--text-2);padding:14px 14px 10px">Recently broken</div>
        <table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead><tr style="border-bottom:.5px solid var(--border)">
                @foreach(['When','Broken','Into','Cost','Per unit','By'] as $h)
                <th style="padding:9px 12px;text-align:{{ in_array($h,['Cost','Per unit']) ? 'right' : 'left' }};color:var(--text-3);font-weight:500;font-size:11px">{{ $h }}</th>
                @endforeach
            </tr></thead>
            <tbody>
            @forelse($history as $c)
            <tr style="border-bottom:.5px solid var(--surface-3)">
                <td style="padding:9px 12px;color:var(--text-3);white-space:nowrap">{{ $c->created_at?->format('d M · h:i A') }}</td>
                <td style="padding:9px 12px;color:var(--text)">{{ $trim($c->from_qty) }} × {{ $c->from?->name }}</td>
                <td style="padding:9px 12px;color:var(--text)">
                    {{ $trim($c->to_qty) }} {{ $c->to?->unit }} {{ $c->to?->name }}
                    @if($c->hadWastage())
                    <span style="font-size:10px;color:var(--warning-2)"> · {{ $trim($c->wastage_qty) }} short</span>
                    @endif
                </td>
                <td style="padding:9px 12px;text-align:right;color:var(--text-2)">Rs. {{ number_format($c->total_cost, 2) }}</td>
                <td style="padding:9px 12px;text-align:right;color:var(--text-2)">Rs. {{ number_format($c->unit_cost, 2) }}</td>
                <td style="padding:9px 12px;color:var(--text-3)">{{ $c->createdBy?->name ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="padding:26px;text-align:center;color:var(--text-4)">Nothing broken down yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($history->hasPages())<div style="margin-top:12px">{{ $history->links() }}</div>@endif
</div>

{{-- ── Breakdown rules ─────────────────────────────────────────── --}}
<div style="background:var(--surface);border:.5px solid var(--border);border-radius:8px;padding:14px">
    <div style="font-size:12px;font-weight:500;color:var(--text-2);margin-bottom:4px">Breakdowns</div>
    <div style="font-size:10.5px;color:var(--text-4);margin-bottom:10px;line-height:1.5">
        Set once per pair: which bulk product becomes which retail product, and how much
        one pack gives.
    </div>

    <form method="POST" action="{{ route('stock.conversions.rules.store') }}" style="margin-bottom:12px">
    @csrf
    @include('stock._product_picker', [
        'field'  => 'from_product_id',
        'label'  => 'Bulk product',
        'hint'   => 'The pack you open — a 20kg bag',
        'inp'    => $inp,
    ])
    @include('stock._product_picker', [
        'field'  => 'to_product_id',
        'label'  => 'Becomes',
        'hint'   => 'What you sell it as — loose kg, or a small packet',
        'inp'    => $inp,
    ])

    <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">One pack gives</label>
    <input type="number" name="yield_qty" step="0.001" min="0.001" required placeholder="e.g. 20"
           style="{{ $inp }};width:100%;height:32px;margin-bottom:10px">

    <button type="submit" style="width:100%;height:32px;background:var(--primary-soft);border:.5px solid var(--primary-border);border-radius:6px;color:var(--primary-text);font-size:12px;cursor:pointer">Save breakdown</button>
    </form>

    @forelse($rules as $r)
    <div style="display:flex;align-items:flex-start;gap:8px;padding:8px 0;border-top:.5px solid var(--surface-3);font-size:11px">
        <div style="flex:1;min-width:0">
            <div style="color:var(--text)">{{ $r->from?->name }}</div>
            <div style="color:var(--text-3);margin-top:1px">→ {{ $r->to?->name }}</div>
            <div style="color:var(--primary-text);margin-top:2px">{{ $r->label() }}{{ $r->yieldIsFixed() ? ' · fixed' : '' }}</div>
        </div>
        <form method="POST" action="{{ route('stock.conversions.rules.destroy', $r) }}" onsubmit="return confirm('Remove this breakdown? Stock already broken stays as it is.')">
            @csrf @method('DELETE')
            <button type="submit" title="Remove" style="width:24px;height:24px;background:var(--surface-2);border:.5px solid var(--border);border-radius:5px;color:var(--danger);cursor:pointer"><i class="ti ti-trash" style="font-size:11px"></i></button>
        </form>
    </div>
    @empty
    <div style="font-size:11px;color:var(--text-4);padding-top:8px;border-top:.5px solid var(--surface-3)">None yet.</div>
    @endforelse
</div>

</div>
</div>

@push('scripts')
<script>
/**
 * Search-as-you-type product field, backing _product_picker.blade.php.
 *
 * The catalogue runs to thousands of products, so the list is fetched as the
 * person types rather than rendered into the page. `picked` is what the form
 * posts: typing alone never sets it, so a half-finished search can't be
 * submitted as though it were a product.
 */
function productPicker() {
    return {
        q: '',
        results: [],
        picked: null,
        open: false,
        loading: false,
        searched: false,
        cursor: 0,

        fmt(v) { return (Math.round((v || 0) * 1000) / 1000).toLocaleString(); },

        async search() {
            const term = this.q.trim();
            this.picked = null;          // editing the text drops the old choice
            this.cursor = 0;

            if (term.length < 2) { this.results = []; this.searched = false; this.open = false; return; }

            this.loading = true;
            this.open = true;
            try {
                const res = await fetch('/api/products/search?q=' + encodeURIComponent(term), {
                    headers: { 'Accept': 'application/json' },
                });
                this.results = res.ok ? await res.json() : [];
            } catch (e) {
                this.results = [];
            } finally {
                this.loading = false;
                this.searched = true;
            }
        },

        /** Re-show the last results when coming back to a field already typed in. */
        reopen() { if (this.results.length && !this.picked) this.open = true; },

        move(step) {
            if (!this.results.length) return;
            this.cursor = (this.cursor + step + this.results.length) % this.results.length;
        },

        choose(p) {
            if (!p) return;
            this.picked = p;
            this.q = p.name;
            this.results = [];
            this.open = false;
            this.searched = false;
        },

        clear() {
            this.picked = null;
            this.q = '';
            this.results = [];
            this.open = false;
            this.searched = false;
        },
    };
}

function bulkBreak() {
    return {
        ruleId: '',
        rule: null,
        fromQty: 1,
        toQty: '',

        fmt(v) { return (Math.round(v * 1000) / 1000).toLocaleString(); },

        get fromValue() { return parseFloat(this.fromQty) || 0; },

        /** What the rule says this many packs should give. */
        get expected() { return this.rule ? Math.round(this.fromValue * this.rule.yield * 1000) / 1000 : 0; },

        /** A weighed destination is always its full yield; packets are counted. */
        get produced() {
            if (!this.rule) return 0;
            return this.rule.fixed ? this.expected : (parseFloat(this.toQty) || 0);
        },

        get shortfall() { return Math.max(0, Math.round((this.expected - this.produced) * 1000) / 1000); },
        get overYield() { return this.produced > this.expected + 0.0005; },

        syncRule() {
            const opt = this.$el.querySelector(`option[value="${this.ruleId}"]`)
                     || document.querySelector(`option[value="${this.ruleId}"]`);
            if (!this.ruleId || !opt) { this.rule = null; return; }
            this.rule = {
                yield:    parseFloat(opt.dataset.yield) || 0,
                fixed:    opt.dataset.fixed === '1',
                unit:     opt.dataset.unit || 'unit',
                fromUnit: opt.dataset.fromUnit || 'unit',
                stock:    parseFloat(opt.dataset.stock) || 0,
                retail:   parseFloat(opt.dataset.retail) || 0,
            };
            // Start from the usual yield; the person breaking can correct it.
            this.toQty = this.expected;
        },
    };
}
</script>
@endpush
@endsection
