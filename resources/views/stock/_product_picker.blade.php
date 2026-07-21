{{-- stock/_product_picker.blade.php — search-as-you-type product field.

     A plain <select> can't carry a catalogue of a few thousand products, so this
     posts the same hidden field name a select would while looking products up
     through /api/products/search as you type.

     Expects: $field (form field name), $label, $inp (shared input styling).
     Optional: $hint.
--}}
<div x-data="productPicker()" style="position:relative;margin-bottom:8px">
    <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">{{ $label }}</label>

    {{-- The value the form actually posts. Empty until something is picked, so a
         half-typed name can't be submitted as if it were a product. --}}
    <input type="hidden" name="{{ $field }}" :value="picked ? picked.id : ''" required>

    <div style="position:relative">
        <input type="text" x-model="q" @input.debounce.250ms="search()" @focus="reopen()"
               @keydown.escape.stop="open = false"
               @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
               @keydown.enter.prevent="choose(results[cursor])"
               placeholder="Type a name, SKU or barcode…" autocomplete="off"
               style="{{ $inp }};width:100%;height:32px;padding-right:26px">
        <button type="button" x-show="picked || q" x-cloak @click="clear()" tabindex="-1"
                style="position:absolute;right:6px;top:6px;width:20px;height:20px;background:none;border:none;color:var(--text-4);cursor:pointer;font-size:14px;line-height:1">&times;</button>
    </div>

    <div x-show="picked" x-cloak style="font-size:10.5px;color:var(--success);margin-top:3px">
        <span x-text="picked?.name"></span>
        <span style="color:var(--text-4)" x-text="picked ? ' · ' + picked.unit + (picked.is_weighed ? ' · by weight' : '') : ''"></span>
    </div>
    @isset($hint)
    <div x-show="!picked" x-cloak style="font-size:10px;color:var(--text-4);margin-top:3px">{{ $hint }}</div>
    @endisset

    {{-- Results --}}
    <div x-show="open && (results.length || searched)" x-cloak @click.outside="open = false"
         style="position:absolute;left:0;right:0;top:100%;z-index:40;margin-top:3px;background:var(--surface);border:.5px solid var(--border);border-radius:7px;box-shadow:0 8px 24px rgba(0,0,0,.35);max-height:230px;overflow-y:auto">
        <template x-for="(p, i) in results" :key="p.id">
            <button type="button" @click="choose(p)" @mouseenter="cursor = i"
                    :style="cursor === i ? 'background:var(--surface-2)' : 'background:none'"
                    style="display:block;width:100%;text-align:left;border:none;border-bottom:.5px solid var(--surface-3);padding:7px 9px;cursor:pointer">
                <div style="font-size:12px;color:var(--text)" x-text="p.name"></div>
                <div style="font-size:10px;color:var(--text-4);margin-top:1px">
                    <span x-text="p.unit"></span>
                    <template x-if="p.is_weighed"><span> · by weight</span></template>
                    <template x-if="p.sku"><span x-text="' · ' + p.sku"></span></template>
                    <span x-text="' · ' + fmt(p.stock) + ' in stock'"></span>
                </div>
            </button>
        </template>
        <div x-show="!results.length && searched && !loading" style="padding:10px 9px;font-size:11px;color:var(--text-4)">
            Nothing matched “<span x-text="q"></span>”.
        </div>
        <div x-show="loading" style="padding:10px 9px;font-size:11px;color:var(--text-4)">Searching…</div>
    </div>
</div>
