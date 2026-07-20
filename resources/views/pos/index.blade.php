{{-- resources/views/pos/index.blade.php --}}
@extends('layouts.app')

@section('title', 'POS — FreshMart')

@push('styles')
<style>
.pos-wrap{display:grid;grid-template-columns:1fr 340px;height:calc(100vh - 56px);background:var(--bg)}
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;padding:12px;overflow-y:auto;align-content:start}
.prod-card{aspect-ratio:1;background:var(--surface);border:0.5px solid var(--border);border-radius:8px;padding:8px;cursor:pointer;transition:border-color .12s;display:flex;flex-direction:column;gap:4px}
.prod-card:hover{border-color:var(--primary)}
.prod-card:focus-visible{outline:2px solid var(--primary);outline-offset:1px}
.prod-img{flex:1;min-height:0;background:var(--surface-2);border-radius:6px;display:flex;align-items:center;justify-content:center;overflow:hidden;font-size:30px}
.prod-img img{width:100%;height:100%;object-fit:cover}
.prod-name{font-size:12px;color:var(--text);font-weight:500;line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.prod-price{font-size:13px;color:var(--primary-text);font-weight:500}
.prod-stock{font-size:10px;color:var(--text-3)}
.prod-foot{display:flex;justify-content:space-between;align-items:center}
/* Full-screen focus mode hides the side navigation */
body.pos-fullscreen .app-sidebar{display:none!important}
.cart-panel{background:var(--surface);border-left:0.5px solid var(--border);display:flex;flex-direction:column;min-height:0;overflow:hidden}
.cart-list{flex:1;overflow-y:auto;padding:8px 12px;min-height:0}
.cart-fixed{flex-shrink:0}
[x-cloak]{display:none!important}
.cust-input{width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:8px 10px;outline:none;margin-bottom:8px;font-family:inherit}
.amt-input{width:92px;background:var(--bg);border:.5px solid var(--border);border-radius:5px;color:var(--text);font-size:12px;padding:4px 8px;text-align:right;outline:none;font-family:inherit}
.calc-key{height:42px;background:var(--surface-2);border:.5px solid var(--border);border-radius:7px;color:var(--text);font-size:15px;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center}
.calc-key:hover{background:var(--surface-4)}
.calc-key.op{background:var(--primary-soft);color:var(--primary-text);border-color:var(--primary-border)}
.sc-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:.5px solid var(--surface-3);font-size:12px}
.sc-key{font-family:monospace;font-size:11px;background:var(--surface-2);border:.5px solid var(--border);border-radius:4px;padding:2px 7px;color:var(--primary-text)}
input[type=number]::-webkit-inner-spin-button,input[type=number]::-webkit-outer-spin-button{-webkit-appearance:none;margin:0}
input[type=number]{-moz-appearance:textfield}
.ci{padding:8px 0;border-bottom:0.5px solid var(--surface-3)}
.ci:last-child{border-bottom:none}
.ci-active{background:var(--surface-active);box-shadow:inset 2px 0 0 var(--primary);border-radius:4px}
.ci-name{font-size:12px;color:var(--text);font-weight:500}
.ci-meta{font-size:11px;color:var(--text-3);margin-top:1px}
.ci-row{display:flex;align-items:center;justify-content:space-between;margin-top:5px}
.qty-btn{width:22px;height:22px;background:var(--surface-4);border:0.5px solid var(--border);border-radius:4px;color:var(--text-2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px}
.np-key{height:36px;background:var(--surface-2);border:0.5px solid var(--border);border-radius:6px;color:var(--text);font-size:13px;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .1s}
.np-key:hover{background:var(--surface-4)}
.np-key.action{background:var(--primary-soft);color:var(--primary-text);border-color:var(--primary-border)}
.pay-btn{height:40px;border-radius:7px;font-size:13px;font-weight:500;cursor:pointer;border:none;display:flex;align-items:center;justify-content:center;gap:6px;transition:opacity .15s}
.pay-btn:hover{opacity:.85}
.cats{display:flex;gap:6px;padding:10px 12px;border-bottom:0.5px solid var(--border);overflow-x:auto}
.cat-pill{padding:4px 14px;border-radius:20px;font-size:12px;font-weight:500;cursor:pointer;border:0.5px solid var(--border);color:var(--text-2);background:var(--surface);white-space:nowrap}
.cat-pill.active{background:var(--primary-soft);color:var(--primary-text);border-color:var(--primary-border)}
.scan-bar{height:48px;background:var(--surface);border-bottom:0.5px solid var(--border);display:flex;align-items:center;gap:8px;padding:0 12px}
.scan-input{flex:1;background:var(--bg);border:0.5px solid var(--border);border-radius:6px;padding:0 10px;height:34px;color:var(--text);font-size:13px;outline:none;font-family:inherit}
.scan-input:focus{border-color:var(--primary)}
</style>
@endpush

@section('content')
<div class="pos-wrap" x-data="posScreen()">
    {{-- LEFT: Products --}}
    <div style="display:flex;flex-direction:column;overflow:hidden">
        {{-- Scan bar --}}
        <div class="scan-bar">
            <i class="ti ti-scan" style="font-size:16px;color:var(--text-3)"></i>
            <div style="flex:1;position:relative" @click.away="showSuggest=false">
                <input class="scan-input" id="scan-input" x-model="query" autofocus style="width:100%" title="Search (F2)"
                       @focus="numpadTarget='search'; if (query) showSuggest=true"
                       @input.debounce.200ms="searchProducts(query)"
                       @keydown.arrow-down.prevent="moveSuggest(1)"
                       @keydown.arrow-up.prevent="moveSuggest(-1)"
                       @keydown.enter.prevent="chooseSuggest()"
                       @keydown.escape="showSuggest=false"
                       placeholder="Scan barcode or search product...">
                {{-- Auto-suggestions --}}
                <div x-show="showSuggest && suggestions.length" x-cloak
                     style="position:absolute;top:40px;left:0;right:0;background:var(--surface);border:.5px solid var(--border);border-radius:6px;z-index:40;max-height:300px;overflow-y:auto;box-shadow:0 10px 28px var(--shadow)">
                    <template x-for="(s, i) in suggestions" :key="s.id">
                        <div @click="chooseSuggestAt(i)" @mouseenter="suggestIdx=i"
                             :style="i===suggestIdx ? 'background:var(--surface-2)' : 'background:transparent'"
                             style="display:flex;justify-content:space-between;align-items:center;padding:8px 10px;cursor:pointer;border-bottom:.5px solid var(--surface-3)">
                            <div style="min-width:0">
                                <div style="font-size:12px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis" x-text="s.name"></div>
                                <div style="font-size:10px;color:var(--text-3)" x-text="'SKU ' + s.sku + '  ·  ' + (s.barcode || 'No barcode') + '  ·  Stock: ' + s.stock"></div>
                            </div>
                            <div style="font-size:12px;color:var(--primary-text);font-weight:500;white-space:nowrap;margin-left:8px" x-text="'Rs. ' + parseFloat(s.price).toLocaleString()"></div>
                        </div>
                    </template>
                </div>
            </div>
            <button class="np-key action" style="width:92px;height:34px;font-size:12px"
                    @click="openCouponModal()">
                <i class="ti ti-tag" style="font-size:13px"></i> Coupon
            </button>
            <button class="np-key" style="width:104px;height:34px;font-size:12px" title="Customer (F9)"
                    @click="openCustomerSearch()">
                <i class="ti ti-user" style="font-size:13px"></i>
                <span x-text="customer ? customer.name.split(' ')[0] : 'Customer'"></span>
            </button>
            <button class="np-key" style="width:92px;height:34px;font-size:12px"
                    :style="counterOpen ? 'background:var(--success-soft);color:var(--success);border-color:var(--success-border)' : 'background:var(--danger-soft-2);color:var(--danger-text);border-color:var(--danger-soft)'"
                    @click="counterOpen ? closeCounterPrompt() : openCounterPrompt()"
                    :title="counterOpen ? 'Close counter' : 'Open counter'">
                <i class="ti ti-cash" style="font-size:13px"></i>
                <span x-text="counterOpen ? 'Close' : 'Open'"></span>
            </button>
            <button class="np-key" style="width:40px;height:34px" @click="toggleFullscreen()"
                    :title="isFullscreen ? 'Exit full screen (F8)' : 'Full screen (F8)'">
                <i :class="isFullscreen ? 'ti ti-arrows-minimize' : 'ti ti-arrows-maximize'" style="font-size:15px"></i>
            </button>
            <button class="np-key" style="width:36px;height:34px" @click="showCalc=true" title="Calculator">
                <i class="ti ti-calculator" style="font-size:15px"></i>
            </button>
            <button class="np-key" style="width:36px;height:34px" @click="showShortcuts=true" title="Keyboard shortcuts (F1)">
                <i class="ti ti-keyboard" style="font-size:15px"></i>
            </button>
        </div>

        {{-- Categories --}}
        <div class="cats">
            <div class="cat-pill active" @click="filterCategory(null, $el)">All</div>
            @foreach($categories as $cat)
                <div class="cat-pill" @click="filterCategory({{ $cat->id }}, $el)">{{ $cat->name }}</div>
            @endforeach
        </div>

        {{-- Product grid --}}
        <div class="product-grid" id="product-grid">
            <template x-for="p in products" :key="p.id">
                <div class="prod-card" @click="addToCart(p)" tabindex="0" @keydown.enter="addToCart(p)">
                    <div class="prod-img">
                        <template x-if="p.image"><img :src="p.image" alt=""></template>
                        <template x-if="!p.image"><span>📦</span></template>
                    </div>
                    <div class="prod-name" x-text="p.name"></div>
                    <div class="prod-foot">
                        <span class="prod-price" x-text="'Rs. ' + parseFloat(p.price).toLocaleString()"></span>
                        <span class="prod-stock" x-text="'×' + p.stock"></span>
                    </div>
                </div>
            </template>
            <template x-if="products.length === 0">
                <div style="grid-column:1/-1;text-align:center;color:var(--text-4);padding:32px;font-size:13px">
                    <i class="ti ti-search" style="font-size:28px;display:block;margin-bottom:8px"></i>
                    No products found
                </div>
            </template>
        </div>
    </div>

    {{-- RIGHT: Cart + numpad --}}
    <div class="cart-panel">

        {{-- Cart header --}}
        <div class="cart-fixed" style="padding:10px 12px;border-bottom:0.5px solid var(--border)">
            <div style="display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:13px;font-weight:500;color:var(--text)">
                    <i class="ti ti-shopping-cart" style="color:var(--primary);margin-right:4px"></i>
                    Cart <span style="font-size:11px;color:var(--text-3)" x-text="'(' + cartCount + ' items)'"></span>
                </span>
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:11px;color:var(--primary-text);cursor:pointer" @click="openCustomItem()" title="Add a one-off / custom item">
                        <i class="ti ti-plus" style="font-size:12px"></i> Custom
                    </span>
                    <span style="font-size:11px;color:var(--warning-2);cursor:pointer" @click="holdBill()" title="Hold this bill to finish later">
                        <i class="ti ti-player-pause" style="font-size:12px"></i> Hold
                    </span>
                    <span style="font-size:11px;color:var(--info);cursor:pointer" @click="openHeldBills()" title="Resume a held bill">
                        <i class="ti ti-clipboard-list" style="font-size:12px"></i> Held<span x-show="heldBills.length" x-text="' (' + heldBills.length + ')'"></span>
                    </span>
                    <span style="font-size:11px;color:var(--danger-2);cursor:pointer" @click="clearCart()">
                        <i class="ti ti-trash" style="font-size:12px"></i> Clear
                    </span>
                </div>
            </div>
            {{-- Customer row --}}
            <div style="display:flex;align-items:center;gap:6px;margin-top:8px;background:var(--bg);border:0.5px solid var(--border);border-radius:6px;padding:5px 9px">
                <i class="ti ti-user" style="font-size:12px;color:var(--text-3)"></i>
                <template x-if="!customer">
                    <div style="flex:1;display:flex;align-items:center;gap:6px;cursor:pointer" @click="openCustomerSearch()">
                        <span style="font-size:11px;color:var(--text-3);flex:1">Walk-in — tap to search / add</span>
                        <i class="ti ti-search" style="font-size:12px;color:var(--primary)"></i>
                    </div>
                </template>
                <template x-if="customer">
                    <div style="flex:1;display:flex;align-items:center;gap:6px">
                        <span style="font-size:11px;color:var(--text);flex:1" x-text="customer.name + (customer.phone ? ' · ' + customer.phone : '')"></span>
                        <span x-show="customer.credit_approved" x-cloak title="Approved for credit" style="font-size:9px;padding:1px 6px;border-radius:8px;background:var(--success-soft);color:var(--success)">credit</span>
                        <i class="ti ti-x" style="font-size:13px;color:var(--danger-2);cursor:pointer" @click="clearCustomer()" title="Remove customer"></i>
                    </div>
                </template>
            </div>
        </div>

        {{-- Cart items --}}
        <div class="cart-list">
            <template x-if="cart.length === 0">
                <div style="text-align:center;color:var(--text-4);font-size:12px;padding:24px 0">
                    <i class="ti ti-shopping-cart" style="font-size:28px;display:block;margin-bottom:8px"></i>
                    Cart is empty
                </div>
            </template>
            <template x-for="(item, idx) in cart" :key="idx">
                <div class="ci" :id="'cart-item-'+idx" :class="{ 'ci-active': idx === activeIdx }">
                    <div style="display:flex;justify-content:space-between;align-items:start">
                        <div class="ci-name" x-text="item.name"></div>
                        <i class="ti ti-x" style="font-size:13px;color:var(--danger-2);cursor:pointer;margin-left:6px"
                           @click="removeItem(idx)"></i>
                    </div>
                    <div class="ci-meta">
                        <template x-if="editPriceIdx !== idx">
                            <span @click="startEditPrice(idx)" title="Click to change the unit price"
                                  style="cursor:pointer;border-bottom:1px dashed var(--text-5)"
                                  x-text="'Rs. ' + parseFloat(item.price).toLocaleString() + ' each'"></span>
                        </template>
                        <template x-if="editPriceIdx === idx">
                            <span style="display:inline-flex;align-items:center;gap:4px">
                                <span style="color:var(--text-3)">Rs.</span>
                                <input type="number" min="0" step="0.01" x-model.number="item.price" :id="'price-input-'+idx"
                                       @keydown.enter.stop.prevent="commitPrice(idx)" @keydown.escape="editPriceIdx=-1" @blur="commitPrice(idx)"
                                       style="width:78px;background:var(--bg);border:.5px solid var(--primary-border);border-radius:4px;color:var(--text);font-size:11px;padding:2px 6px;outline:none">
                                <span style="color:var(--text-3)">each</span>
                            </span>
                        </template>
                    </div>
                    <div class="ci-row">
                        <div style="display:flex;align-items:center;gap:5px">
                            <div class="qty-btn" @click="changeQty(idx, -1)">−</div>
                            <span style="font-size:12px;color:var(--text);min-width:20px;text-align:center" x-text="item.qty"></span>
                            <div class="qty-btn" @click="changeQty(idx, 1)">+</div>
                        </div>
                        <span style="font-size:13px;color:var(--primary-text);font-weight:500"
                              x-text="'Rs. ' + (item.price * item.qty).toLocaleString()"></span>
                    </div>
                </div>
            </template>
        </div>

        {{-- Totals --}}
        <div class="cart-fixed" style="padding:10px 12px;border-top:0.5px solid var(--border)">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-2);margin-bottom:5px">
                <span>Subtotal</span><span x-text="'Rs. ' + subtotal.toLocaleString()"></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--text-2);margin-bottom:5px">
                <span>Discount <span style="color:var(--success)" x-show="couponCode" x-text="couponCode ? '(' + couponCode + ')' : ''"></span></span>
                <div style="display:flex;align-items:center;gap:5px">
                    <button type="button" @click="discountMode = discountMode === 'amount' ? 'percent' : 'amount'"
                            style="width:34px;height:26px;background:var(--surface-2);border:.5px solid var(--primary-border);border-radius:5px;color:var(--primary-text);font-size:11px;font-weight:600;cursor:pointer"
                            x-text="discountMode === 'amount' ? 'Rs' : '%'"></button>
                    <input type="text" inputmode="decimal" x-model="discountInput" placeholder="0" class="amt-input"
                           @focus="numpadTarget='discount'"
                           @input="discountInput = discountInput.replace(/[^0-9.]/g,'').replace(/(\..*)\./g,'$1')">
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-3);margin-bottom:3px" x-show="discountValue > 0">
                <span>Discount applied</span><span x-text="'- Rs. ' + discountValue.toLocaleString()"></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--text-2);margin-bottom:5px">
                <span>Tax (%)</span>
                <input type="text" inputmode="decimal" x-model="taxPercent" placeholder="0" class="amt-input"
                       @focus="numpadTarget='tax'"
                       @input="taxPercent = taxPercent.replace(/[^0-9.]/g,'').replace(/(\..*)\./g,'$1')">
            </div>
            <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-3);margin-bottom:3px" x-show="tax > 0">
                <span>Tax amount</span><span x-text="'Rs. ' + tax.toLocaleString()"></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:14px;color:var(--text);font-weight:500;padding-top:6px;border-top:0.5px solid var(--border);margin-top:4px">
                <span>Total</span><span x-text="'Rs. ' + total.toLocaleString()"></span>
            </div>
        </div>

        {{-- Numpad --}}
        <div class="cart-fixed" style="padding:8px 12px;border-top:0.5px solid var(--border)">
            <div style="background:var(--bg);border:0.5px solid var(--border);border-radius:6px;padding:6px 10px;margin-bottom:8px">
                <div style="font-size:10px;color:var(--text-3)">Cash received <span style="color:var(--text-5)">(F3)</span></div>
                <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px">
                    <span style="font-size:14px;color:var(--text-3)">Rs.</span>
                    <input type="text" inputmode="decimal" id="cash-input" x-model="cashStr"
                           @focus="numpadTarget='cash'; $event.target.select()"
                           @input="cashStr = cashStr.replace(/[^0-9.]/g,'').replace(/(\..*)\./g,'$1')"
                           style="width:130px;background:transparent;border:none;outline:none;color:var(--text);font-size:20px;font-weight:500;text-align:right;font-family:inherit;padding:0">
                </div>
                <div style="font-size:11px;color:var(--success);text-align:right" x-show="cashNum >= total && cart.length > 0"
                     x-text="'Change: Rs. ' + Math.max(0, cashNum - total).toLocaleString()"></div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:5px;margin-bottom:6px">
                <div class="np-key" @click="np('7')">7</div>
                <div class="np-key" @click="np('8')">8</div>
                <div class="np-key" @click="np('9')">9</div>
                <div class="np-key" @click="npDel()"><i class="ti ti-backspace"></i></div>
                <div class="np-key" @click="np('4')">4</div>
                <div class="np-key" @click="np('5')">5</div>
                <div class="np-key" @click="np('6')">6</div>
                <div class="np-key action" @click="npExact()" title="Exact cash (F7)">Exact</div>
                <div class="np-key" @click="np('1')">1</div>
                <div class="np-key" @click="np('2')">2</div>
                <div class="np-key" @click="np('3')">3</div>
                <div class="np-key" @click="np('0')">0</div>
                <div class="np-key" @click="np('00')" style="grid-column:span 2">00</div>
                <div class="np-key" @click="np('.')">.</div>
                <div class="np-key" @click="np('000')">000</div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                <button class="pay-btn" style="background:var(--success-solid);color:#fff" @click="pay('cash')">
                    <i class="ti ti-cash"></i> Cash
                </button>
                <button class="pay-btn" style="background:var(--primary-solid);color:#fff" @click="pay('card')">
                    <i class="ti ti-credit-card"></i> Card
                </button>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:6px">
                <button class="pay-btn" style="background:var(--info-solid);color:#fff" @click="openSplit()" title="Split / credit payment (F5)">
                    <i class="ti ti-arrows-split-2"></i> Split
                </button>
                <button class="pay-btn" style="background:var(--warning-solid);color:#fff" @click="openCredit()" title="Sell on credit">
                    <i class="ti ti-calendar-due"></i> Credit
                </button>
            </div>
        </div>
    </div>

    {{-- New customer modal --}}
    <template x-teleport="body">
    <div x-show="showCustomerModal" x-cloak @keydown.escape.window="showCustomerModal=false" @click.self="showCustomerModal=false"
         style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:50">
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:10px;padding:18px;width:330px">
            <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:12px;display:flex;align-items:center;gap:6px">
                <i class="ti ti-user" style="color:var(--primary)"></i> Customer
            </div>

            {{-- Search existing --}}
            <input class="cust-input" x-model="customerQuery" @input.debounce.300ms="searchCustomers()" placeholder="Search name or phone...">
            <div x-show="customerResults.length" x-cloak style="max-height:150px;overflow-y:auto;border:.5px solid var(--border);border-radius:6px;margin-bottom:8px">
                <template x-for="c in customerResults" :key="c.id">
                    <div @click="selectCustomer(c)" @mouseover="$el.style.background='var(--surface-2)'" @mouseout="$el.style.background='transparent'"
                         style="display:flex;justify-content:space-between;padding:7px 10px;cursor:pointer;border-bottom:.5px solid var(--surface-3);font-size:12px">
                        <span style="color:var(--text)" x-text="c.name"></span>
                        <span style="color:var(--text-3)" x-text="c.phone || ''"></span>
                    </div>
                </template>
            </div>
            <div x-show="customerQuery && !customerResults.length && !customerSearching" x-cloak style="text-align:center;color:var(--text-3);font-size:11px;margin-bottom:8px">No matches — register below</div>

            <div style="display:flex;align-items:center;gap:8px;margin:10px 0">
                <div style="flex:1;height:1px;background:var(--border)"></div>
                <span style="font-size:10px;color:var(--text-5);letter-spacing:.5px">OR REGISTER NEW</span>
                <div style="flex:1;height:1px;background:var(--border)"></div>
            </div>

            <input class="cust-input" x-model="newCustomer.name" placeholder="Full name *" @keydown.enter="saveCustomer()">
            <input class="cust-input" x-model="newCustomer.phone" placeholder="Phone" @keydown.enter="saveCustomer()">
            <input class="cust-input" x-model="newCustomer.nic" placeholder="NIC (needed for credit)" @keydown.enter="saveCustomer()">
            <input class="cust-input" x-model="newCustomer.address" placeholder="Address (for credit bill)" @keydown.enter="saveCustomer()">
            <input class="cust-input" type="email" x-model="newCustomer.email" placeholder="Email (optional)" @keydown.enter="saveCustomer()">
            <div x-show="customerError" x-cloak x-text="customerError" style="color:var(--danger);font-size:11px;margin-bottom:8px"></div>
            <div style="display:flex;gap:8px;margin-top:4px">
                <button @click="showCustomerModal=false" style="flex:1;height:36px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Cancel</button>
                <button @click="saveCustomer()" style="flex:1;height:36px;background:var(--success-soft);border:.5px solid var(--success-border);border-radius:6px;color:var(--success);font-size:12px;font-weight:500;cursor:pointer">Save &amp; select</button>
            </div>
        </div>
    </div>
    </template>

    {{-- Sale success popup --}}
    <template x-teleport="body">
    <div x-show="showSaleModal" x-cloak @keydown.escape.window="if (!(lastSale && lastSale.is_credit)) showSaleModal=false"
         style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:50">
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:12px;padding:22px;width:320px;text-align:center">
            <div style="width:52px;height:52px;border-radius:50%;background:var(--success-soft);display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
                <i class="ti ti-check" style="font-size:28px;color:var(--success)"></i>
            </div>
            <div style="font-size:15px;font-weight:600;color:var(--text);margin-bottom:4px" x-text="lastSale && lastSale.is_credit ? 'Credit sale recorded' : 'Payment successful'"></div>
            <div style="font-size:12px;color:var(--text-3);margin-bottom:14px" x-text="lastSale ? 'Invoice ' + lastSale.invoice_no : ''"></div>
            <div style="background:var(--bg);border:.5px solid var(--border);border-radius:8px;padding:12px;margin-bottom:16px">
                <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text);margin-bottom:4px">
                    <span>Total</span><span x-text="lastSale ? 'Rs. ' + Number(lastSale.total).toLocaleString() : ''"></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--success)" x-show="lastSale && lastSale.change > 0">
                    <span>Change</span><span x-text="lastSale ? 'Rs. ' + Number(lastSale.change).toLocaleString() : ''"></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--warning-2);margin-top:4px" x-show="lastSale && lastSale.is_credit">
                    <span>On credit</span><span x-text="lastSale ? 'Rs. ' + Number(lastSale.balance_due).toLocaleString() : ''"></span>
                </div>
            </div>
            {{-- Non-credit sale: close + print --}}
            <template x-if="!(lastSale && lastSale.is_credit)">
            <div style="display:flex;gap:8px">
                <button @click="showSaleModal=false" style="flex:1;height:38px;background:var(--surface-2);border:.5px solid var(--border);border-radius:7px;color:var(--text-2);font-size:13px;cursor:pointer">Close</button>
                <button x-ref="printBtn" @click="printReceipt()" style="flex:1;height:38px;background:var(--primary-soft);border:.5px solid var(--primary-border);border-radius:7px;color:var(--primary-text);font-size:13px;font-weight:500;cursor:pointer">Next <i class="ti ti-arrow-right" style="font-size:13px"></i></button>
            </div>
            </template>
            {{-- Credit sale: BOTH steps are required — print the bill, then attach the signed photo. No skip. --}}
            <template x-if="lastSale && lastSale.is_credit">
            <div>
                <div style="font-size:11px;color:var(--warning-2);background:var(--warning-soft-3);border:.5px solid var(--warning-border-2);border-radius:6px;padding:7px 9px;margin-bottom:10px;text-align:center">Print the bill, get it signed, then attach the signed copy — both are required.</div>
                <button x-ref="printBtn" @click="printCreditBill()" style="width:100%;height:38px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer"
                        :style="creditBillPrinted ? 'background:var(--success-soft);border:.5px solid var(--success-border);color:var(--success)' : 'background:var(--primary-soft);border:.5px solid var(--primary-border);color:var(--primary-text)'">
                    <i class="ti" :class="creditBillPrinted ? 'ti-check' : 'ti-printer'" style="font-size:14px"></i>
                    <span x-text="creditBillPrinted ? 'Bill printed — reprint' : 'Print credit bill'"></span>
                </button>
                <button @click="openEvidence()" :disabled="!creditBillPrinted" :style="!creditBillPrinted ? 'opacity:.5;cursor:not-allowed' : ''"
                        style="width:100%;height:38px;margin-top:8px;background:var(--success-soft);border:.5px solid var(--success-border);border-radius:7px;color:var(--success);font-size:13px;font-weight:600;cursor:pointer">
                    <i class="ti ti-signature" style="font-size:14px"></i> Attach signed copy
                </button>
            </div>
            </template>
        </div>
    </div>
    </template>

    {{-- Card payment popup --}}
    <template x-teleport="body">
    <div x-show="showCardModal" x-cloak @keydown.escape.window="showCardModal=false" @click.self="showCardModal=false"
         style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:50">
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:12px;padding:20px;width:300px">
            <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:4px;display:flex;align-items:center;gap:6px">
                <i class="ti ti-credit-card" style="color:var(--primary)"></i> Card payment
            </div>
            <div style="font-size:11px;color:var(--text-3);margin-bottom:12px" x-text="'Amount: Rs. ' + total.toLocaleString()"></div>
            <label style="font-size:11px;color:var(--text-3)">Last 4 digits of card *</label>
            <input x-ref="cardInput" x-model="cardLast4" inputmode="numeric" maxlength="4" placeholder="1234" class="cust-input"
                   style="margin-top:4px;text-align:center;letter-spacing:5px;font-size:16px"
                   @input="cardLast4 = cardLast4.replace(/\D/g,'').slice(0,4); if (cardLast4.length === 4) $nextTick(() => $refs.cardBtn.focus())"
                   @keydown.enter="confirmCard()">
            <div style="display:flex;gap:8px;margin-top:4px">
                <button @click="showCardModal=false" style="flex:1;height:36px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Cancel</button>
                <button x-ref="cardBtn" @click="confirmCard()" :disabled="cardLast4.length !== 4"
                        @keydown.backspace.prevent="cardLast4 = cardLast4.slice(0,-1); $refs.cardInput.focus()"
                        style="flex:1;height:36px;background:var(--primary-solid);border:none;border-radius:6px;color:#fff;font-size:12px;font-weight:500;cursor:pointer">Pay by card</button>
            </div>
        </div>
    </div>
    </template>

    {{-- Price chooser popup (products with multiple in-stock sale prices) --}}
    <template x-teleport="body">
    <div x-show="showPriceModal" x-cloak @keydown.escape.window="showPriceModal=false" @click.self="showPriceModal=false"
         style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:60">
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:10px;padding:18px;width:320px">
            <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:2px">Choose price</div>
            <div style="font-size:11px;color:var(--text-3);margin-bottom:12px" x-text="priceChooserProduct?.name"></div>
            <div style="display:flex;flex-direction:column;gap:8px">
                <template x-for="opt in priceChooserOptions" :key="opt">
                    <button type="button" @click="pickPrice(opt)"
                        style="height:44px;background:var(--surface-2);border:.5px solid var(--border);border-radius:7px;color:var(--text);font-size:15px;font-weight:600;cursor:pointer"
                        x-text="'Rs. ' + Number(opt).toLocaleString()"></button>
                </template>
            </div>
            <button type="button" @click="showPriceModal=false" style="width:100%;height:34px;margin-top:12px;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Cancel</button>
        </div>
    </div>
    </template>

    {{-- Split / credit payment (cash + card + credit) popup --}}
    <template x-teleport="body">
    <div x-show="showSplitModal" x-cloak @keydown.escape.window="showSplitModal=false" @click.self="showSplitModal=false"
         style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:60">
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:10px;padding:18px;width:340px;max-height:94vh;overflow-y:auto">
            <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:2px">Split / credit payment</div>
            <div style="font-size:11px;color:var(--text-3);margin-bottom:12px" x-text="'Total: Rs. ' + total.toLocaleString()"></div>

            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Card amount (Rs.)</label>
            <input type="number" min="0" step="0.01" x-model.number="splitCard" x-ref="splitCardInput" placeholder="0.00"
                   @keydown.enter.prevent="$refs.splitLast4Input.focus()"
                   style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:13px;padding:8px 10px;outline:none;margin-bottom:8px">

            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Card last 4 digits</label>
            <input type="text" inputmode="numeric" maxlength="4" x-model="splitLast4" x-ref="splitLast4Input" placeholder="1234"
                   @input="splitLast4 = splitLast4.replace(/\D/g,'').slice(0,4)"
                   @keydown.enter.prevent="$refs.splitCashInput.focus()"
                   style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:13px;padding:8px 10px;outline:none;letter-spacing:3px;text-align:center;margin-bottom:8px">

            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Cash amount (Rs.)</label>
            <input type="number" min="0" step="0.01" x-model.number="splitCash" x-ref="splitCashInput" placeholder="0.00"
                   @keydown.enter.prevent="$refs.splitCreditInput.focus()"
                   style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:13px;padding:8px 10px;outline:none;margin-bottom:8px">

            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Credit — on account (Rs.)</label>
            <input type="number" min="0" step="0.01" x-model.number="splitCredit" x-ref="splitCreditInput" placeholder="0.00"
                   @keydown.enter.prevent="if (splitValid) $refs.splitBtn.focus()"
                   style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:13px;padding:8px 10px;outline:none;margin-bottom:8px">

            <div x-show="splitCreditNum > 0" x-cloak style="font-size:11px;border-radius:6px;padding:7px 9px;margin-bottom:10px"
                 :style="creditCustomerOk ? 'background:var(--success-soft-2);border:.5px solid var(--success-soft);color:var(--success)' : 'background:var(--danger-soft-3);border:.5px solid var(--danger-soft);color:var(--danger-text)'">
                <span x-show="creditCustomerOk">On account for <b x-text="customer ? customer.name : ''"></b> · NIC <span x-text="customer && customer.nic ? customer.nic : '—'"></span></span>
                <span x-show="!creditCustomerOk" x-text="creditCustomerError"></span>
            </div>

            <div style="background:var(--bg);border:.5px solid var(--border);border-radius:6px;padding:8px 10px;font-size:12px;margin-bottom:12px">
                <div style="display:flex;justify-content:space-between;color:var(--text-2)"><span>Cash needed</span><span x-text="'Rs. ' + splitCashNeeded.toLocaleString()"></span></div>
                <div style="display:flex;justify-content:space-between;color:var(--text-2);margin-top:3px"><span>Change</span><span x-text="'Rs. ' + splitChange.toLocaleString()"></span></div>
                <div x-show="splitCreditNum > 0" style="display:flex;justify-content:space-between;color:var(--warning-2);margin-top:3px"><span>On credit</span><span x-text="'Rs. ' + splitCreditNum.toLocaleString()"></span></div>
                <div x-show="splitError" style="color:var(--danger);margin-top:5px;font-size:11px" x-text="splitError"></div>
            </div>

            <div style="display:flex;gap:8px">
                <button @click="showSplitModal=false" style="flex:1;height:36px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Cancel</button>
                <button @click="confirmSplit()" x-ref="splitBtn" :disabled="!splitValid" @keydown.enter.prevent="confirmSplit()" :style="!splitValid?'opacity:.5;cursor:not-allowed':''"
                        style="flex:1;height:36px;background:var(--primary-solid);border:none;border-radius:6px;color:#fff;font-size:12px;font-weight:600;cursor:pointer">Complete sale</button>
            </div>
        </div>
    </div>
    </template>

    {{-- Credit signed-document evidence popup (webcam or phone QR) --}}
    <template x-teleport="body">
    <div x-show="showEvidenceModal" x-cloak @keydown.escape.window="evidenceDone ? closeEvidence() : evBack()"
         style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:70">
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:12px;padding:18px;width:400px;max-height:94vh;overflow-y:auto">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                <div style="font-size:14px;font-weight:600;color:var(--text);display:flex;align-items:center;gap:6px"><i class="ti ti-signature" style="color:var(--primary)"></i> Signed copy</div>
                <i class="ti ti-x" @click="closeEvidence()" style="font-size:16px;color:var(--text-3);cursor:pointer"></i>
            </div>
            <div style="font-size:11px;color:var(--text-3);margin-bottom:12px" x-text="lastSale ? 'Invoice ' + lastSale.invoice_no + ' · photograph the signed bill' : ''"></div>

            {{-- Done --}}
            <template x-if="evidenceDone">
                <div style="text-align:center;padding:18px 0">
                    <div style="width:52px;height:52px;border-radius:50%;background:var(--success-soft);display:flex;align-items:center;justify-content:center;margin:0 auto 12px"><i class="ti ti-check" style="font-size:28px;color:var(--success)"></i></div>
                    <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:4px">Signed copy attached</div>
                    <div style="font-size:12px;color:var(--text-3);margin-bottom:16px">Saved as evidence for this credit sale.</div>
                    <button @click="closeEvidence()" style="width:100%;height:38px;background:var(--primary-soft);border:.5px solid var(--primary-border);border-radius:7px;color:var(--primary-text);font-size:13px;font-weight:600;cursor:pointer">Done</button>
                </div>
            </template>

            {{-- Capture --}}
            <template x-if="!evidenceDone">
              <div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:12px">
                    <button @click="switchEvidenceTab('webcam')" :style="evidenceTab==='webcam' ? 'background:var(--primary-soft);color:var(--primary-text);border-color:var(--primary-border)' : 'background:var(--surface-2);color:var(--text-2);border-color:var(--border)'"
                            style="height:34px;border:.5px solid;border-radius:7px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-camera" style="font-size:13px"></i> Webcam</button>
                    <button @click="switchEvidenceTab('phone')" :style="evidenceTab==='phone' ? 'background:var(--primary-soft);color:var(--primary-text);border-color:var(--primary-border)' : 'background:var(--surface-2);color:var(--text-2);border-color:var(--border)'"
                            style="height:34px;border:.5px solid;border-radius:7px;font-size:12px;font-weight:500;cursor:pointer"><i class="ti ti-device-mobile" style="font-size:13px"></i> Use my phone</button>
                </div>

                {{-- Webcam --}}
                <div x-show="evidenceTab==='webcam'">
                    <video x-ref="evidenceVideo" autoplay playsinline muted style="width:100%;border-radius:8px;background:var(--bg);aspect-ratio:4/3;object-fit:cover"></video>
                    <button @click="captureWebcam()" :disabled="evidenceBusy" style="width:100%;height:40px;margin-top:10px;background:var(--success-soft);border:.5px solid var(--success-border);border-radius:7px;color:var(--success);font-size:13px;font-weight:600;cursor:pointer"><i class="ti ti-camera" style="font-size:14px"></i> <span x-text="evidenceBusy ? 'Uploading…' : 'Capture & upload'"></span></button>
                </div>

                {{-- Phone / QR --}}
                <div x-show="evidenceTab==='phone'" style="text-align:center">
                    <div style="font-size:12px;color:var(--text-2);margin-bottom:10px">Scan with the cashier's phone, then enter the code on the phone.</div>
                    <div style="background:#fff;border-radius:8px;padding:10px;display:inline-block;min-width:180px;min-height:180px" x-html="qrSvg"></div>
                    <div x-show="qrLoading" x-cloak style="font-size:11px;color:var(--text-3);margin-top:8px">Creating link…</div>
                    <div x-show="qrCode" x-cloak style="margin-top:12px">
                        <div style="font-size:11px;color:var(--text-3)">Security code</div>
                        <div style="font-size:26px;font-weight:700;letter-spacing:6px;color:var(--text)" x-text="qrCode"></div>
                        <div style="font-size:10px;color:var(--text-5);margin-top:2px">Or the cashier can enter their own login password on the phone.</div>
                    </div>
                    <div style="font-size:11px;color:var(--primary-text);margin-top:12px"><i class="ti ti-loader-2" style="font-size:12px"></i> Waiting for the phone upload…</div>
                </div>

                <div x-show="evidenceMsg" x-cloak style="font-size:11px;color:var(--danger);margin-top:10px;text-align:center" x-text="evidenceMsg"></div>
                <button @click="evBack()" style="width:100%;height:34px;margin-top:12px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer"><i class="ti ti-arrow-left" style="font-size:12px"></i> Back (print / reprint)</button>
              </div>
            </template>
        </div>
    </div>
    </template>

    {{-- Held / parked bills popup --}}
    <template x-teleport="body">
    <div x-show="showHeldModal" x-cloak @keydown.escape.window="showHeldModal=false" @click.self="showHeldModal=false"
         style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:60">
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:10px;padding:16px;width:420px;max-height:80vh;display:flex;flex-direction:column">
            <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:2px">Held bills</div>
            <div style="font-size:11px;color:var(--text-3);margin-bottom:12px">Resume a parked bill, or discard it.</div>
            <div style="overflow-y:auto;flex:1">
                <template x-if="heldBills.length === 0">
                    <div style="text-align:center;color:var(--text-4);font-size:12px;padding:24px">No held bills.</div>
                </template>
                <template x-for="b in heldBills" :key="b.id">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;background:var(--bg);border:.5px solid var(--border);border-radius:7px;padding:9px 11px;margin-bottom:7px">
                        <div style="min-width:0">
                            <div style="font-size:12px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                                 x-text="b.label || ('Bill #' + b.id)"></div>
                            <div style="font-size:10px;color:var(--text-3)" x-text="b.item_count + ' item(s) · Rs. ' + Number(b.total).toLocaleString()"></div>
                        </div>
                        <div style="display:flex;gap:6px;flex-shrink:0">
                            <button @click="resumeBill(b.id)" style="height:30px;padding:0 12px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:11px;font-weight:600;cursor:pointer">Resume</button>
                            <button @click="discardBill(b.id)" title="Discard" style="width:30px;height:30px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--danger);cursor:pointer"><i class="ti ti-trash" style="font-size:13px"></i></button>
                        </div>
                    </div>
                </template>
            </div>
            <button @click="showHeldModal=false" style="width:100%;height:34px;margin-top:10px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Close</button>
        </div>
    </div>
    </template>

    {{-- Custom / temporary item popup --}}
    <template x-teleport="body">
    <div x-show="showCustomModal" x-cloak @keydown.escape.window="showCustomModal=false" @click.self="showCustomModal=false"
         style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:60">
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:10px;padding:18px;width:340px">
            <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:2px">Custom item</div>
            <div style="font-size:11px;color:var(--text-3);margin-bottom:14px">A one-off item that isn't in the catalogue. It won't affect stock.</div>
            <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Item name *</label>
            <input x-ref="customNameInput" x-model="customName" placeholder="e.g. Miscellaneous" @keydown.enter="$refs.customPriceInput.focus()"
                   style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:8px 10px;outline:none;margin-bottom:10px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px">
                <div>
                    <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Unit price (Rs.) *</label>
                    <input x-ref="customPriceInput" type="number" min="0" step="0.01" x-model.number="customPrice" placeholder="0.00"
                           @keydown.enter="addCustomItem()"
                           style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:8px 10px;outline:none">
                </div>
                <div>
                    <label style="display:block;font-size:11px;color:var(--text-3);margin-bottom:4px">Quantity</label>
                    <input type="number" min="0.001" step="0.001" x-model.number="customQty"
                           style="width:100%;background:var(--bg);border:.5px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;padding:8px 10px;outline:none">
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <button @click="showCustomModal=false" style="flex:1;height:36px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Cancel</button>
                <button @click="addCustomItem()" style="flex:1;height:36px;background:var(--success-soft);color:var(--success);border:.5px solid var(--success-border);border-radius:6px;font-size:12px;font-weight:600;cursor:pointer">Add to cart</button>
            </div>
        </div>
    </div>
    </template>

    {{-- Cash payment popup --}}
    <template x-teleport="body">
    <div x-show="showCashModal" x-cloak @keydown.escape.window="showCashModal=false" @click.self="showCashModal=false"
         style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:50">
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:12px;padding:20px;width:300px">
            <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:12px;display:flex;align-items:center;gap:6px">
                <i class="ti ti-cash" style="color:var(--success)"></i> Cash payment
            </div>
            <div style="background:var(--bg);border:.5px solid var(--border);border-radius:8px;padding:12px;margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-2);margin-bottom:5px"><span>Total</span><span x-text="'Rs. ' + total.toLocaleString()"></span></div>
                <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-2);margin-bottom:5px"><span>Cash received</span><span x-text="'Rs. ' + cashNum.toLocaleString()"></span></div>
                <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--success);font-weight:500;padding-top:6px;border-top:.5px solid var(--border)"><span>Change</span><span x-text="'Rs. ' + Math.max(0, cashNum - total).toLocaleString()"></span></div>
            </div>
            <div style="display:flex;gap:8px">
                <button @click="showCashModal=false" style="flex:1;height:36px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Cancel</button>
                <button x-ref="cashBtn" @click="confirmCash()" style="flex:1;height:36px;background:var(--success-solid);border:none;border-radius:6px;color:#fff;font-size:12px;font-weight:600;cursor:pointer">Complete sale</button>
            </div>
        </div>
    </div>
    </template>

    {{-- Open counter popup (required — blocks POS until a session is open) --}}
    <template x-teleport="body">
    <div x-show="showOpenModal" x-cloak
         style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:50">
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:12px;padding:18px;width:340px;max-height:92vh;overflow-y:auto">
            <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:4px;display:flex;align-items:center;gap:6px">
                <i class="ti ti-cash" style="color:var(--success)"></i> Open counter
            </div>
            <div style="font-size:11px;color:var(--text-3);margin-bottom:12px">Count the cash float — type a count, press <b style="color:var(--text-2)">Tab</b> for the next.</div>
            <div x-show="prevClose" x-cloak style="font-size:11px;color:var(--warning-2);background:var(--warning-soft-3);border:.5px solid var(--warning-border-2);border-radius:6px;padding:6px 9px;margin-bottom:10px">
                Last close was <b x-text="'Rs. ' + (prevClose ? prevClose.balance.toLocaleString() : '')"></b> — the drawer should match this.
            </div>
            <template x-for="d in denoms" :key="d">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px">
                    <span style="width:58px;font-size:12px;color:var(--text-2);text-align:right">Rs. <span x-text="d.toLocaleString()"></span></span>
                    <span style="color:var(--text-5)">×</span>
                    <input type="text" inputmode="numeric" class="amt-input denom-open" style="width:64px;text-align:center"
                           x-model="openDenoms[d]" @focus="$event.target.select()"
                           @input="openDenoms[d] = String(openDenoms[d]).replace(/\D/g,'')">
                    <span style="flex:1;text-align:right;font-size:12px;color:var(--text)" x-text="'Rs. ' + (d * (parseInt(openDenoms[d])||0)).toLocaleString()"></span>
                </div>
            </template>
            <div style="display:flex;justify-content:space-between;font-size:14px;color:var(--text);font-weight:600;padding-top:8px;margin-top:6px;border-top:.5px solid var(--border)">
                <span>Opening float</span><span x-text="'Rs. ' + openTotal.toLocaleString()"></span>
            </div>
            <div x-show="openError" x-cloak x-text="openError" style="color:var(--danger);font-size:11px;margin-top:8px"></div>
            <div style="display:flex;gap:8px;margin-top:14px">
                <a href="{{ route('dashboard') }}" style="flex:1;height:36px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;text-decoration:none">Exit POS</a>
                <button @click="submitOpen()" style="flex:1;height:36px;background:var(--success-solid);border:none;border-radius:6px;color:#fff;font-size:12px;font-weight:600;cursor:pointer">Open counter</button>
            </div>
        </div>
    </div>
    </template>

    {{-- Close counter popup --}}
    <template x-teleport="body">
    <div x-show="showCloseModal" x-cloak @keydown.escape.window="!closeResult && (showCloseModal=false)" @click.self="!closeResult && (showCloseModal=false)"
         style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:50">
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:12px;padding:18px;width:340px;max-height:92vh;overflow-y:auto">
            <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:12px;display:flex;align-items:center;gap:6px">
                <i class="ti ti-lock" style="color:var(--warning-2)"></i> Close counter
            </div>

            {{-- Counting form --}}
            <template x-if="!closeResult">
            <div>
                <div style="background:var(--bg);border:.5px solid var(--border);border-radius:8px;padding:10px;margin-bottom:12px;font-size:12px">
                    <div style="display:flex;justify-content:space-between;color:var(--text-2);margin-bottom:4px"><span>Opening float</span><span x-text="'Rs. ' + openingBalance.toLocaleString()"></span></div>
                    <div style="display:flex;justify-content:space-between;color:var(--text-2);margin-bottom:4px"><span>Cash sales</span><span x-text="'Rs. ' + cashSalesSoFar.toLocaleString()"></span></div>
                    <div style="display:flex;justify-content:space-between;color:var(--text);font-weight:600;padding-top:5px;border-top:.5px solid var(--border)"><span>Expected in drawer</span><span x-text="'Rs. ' + closeExpected.toLocaleString()"></span></div>
                </div>
                <div style="font-size:11px;color:var(--text-3);margin-bottom:8px">Count the cash now — type a count, <b style="color:var(--text-2)">Tab</b> for the next:</div>
                <template x-for="d in denoms" :key="d">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px">
                        <span style="width:58px;font-size:12px;color:var(--text-2);text-align:right">Rs. <span x-text="d.toLocaleString()"></span></span>
                        <span style="color:var(--text-5)">×</span>
                        <input type="text" inputmode="numeric" class="amt-input denom-close" style="width:64px;text-align:center"
                               x-model="closeDenoms[d]" @focus="$event.target.select()"
                               @input="closeDenoms[d] = String(closeDenoms[d]).replace(/\D/g,'')">
                        <span style="flex:1;text-align:right;font-size:12px;color:var(--text)" x-text="'Rs. ' + (d * (parseInt(closeDenoms[d])||0)).toLocaleString()"></span>
                    </div>
                </template>
                <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--text);font-weight:600;padding-top:8px;margin-top:6px;border-top:.5px solid var(--border)"><span>Counted</span><span x-text="'Rs. ' + closeTotal.toLocaleString()"></span></div>
                <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:600;margin-top:4px"
                     :style="closeVariance===0 ? 'color:var(--success)' : 'color:var(--danger)'">
                    <span x-text="closeVariance===0 ? 'Balanced' : (closeVariance>0 ? 'Over by' : 'Short by')"></span>
                    <span x-text="'Rs. ' + Math.abs(closeVariance).toLocaleString()"></span>
                </div>
                <div x-show="closeError" x-cloak x-text="closeError" style="color:#f87171;font-size:11px;margin-top:8px"></div>
                <div style="display:flex;gap:8px;margin-top:14px">
                    <button @click="showCloseModal=false" style="flex:1;height:36px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Cancel</button>
                    <button @click="submitClose()" style="flex:1;height:36px;background:var(--danger-solid);border:none;border-radius:6px;color:#fff;font-size:12px;font-weight:600;cursor:pointer">Close counter</button>
                </div>
            </div>
            </template>

            {{-- Result --}}
            <template x-if="closeResult">
            <div style="text-align:center">
                <div style="width:48px;height:48px;border-radius:50%;background:var(--success-soft);display:flex;align-items:center;justify-content:center;margin:4px auto 12px">
                    <i class="ti ti-check" style="font-size:24px;color:var(--success)"></i>
                </div>
                <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:12px">Counter closed</div>
                <div style="background:var(--bg);border:.5px solid var(--border);border-radius:8px;padding:12px;margin-bottom:14px;text-align:left;font-size:12px">
                    <div style="display:flex;justify-content:space-between;color:var(--text-2);margin-bottom:4px"><span>Expected</span><span x-text="closeResult ? 'Rs. ' + closeResult.expected.toLocaleString() : ''"></span></div>
                    <div style="display:flex;justify-content:space-between;color:var(--text-2);margin-bottom:4px"><span>Counted</span><span x-text="closeResult ? 'Rs. ' + closeResult.counted.toLocaleString() : ''"></span></div>
                    <div style="display:flex;justify-content:space-between;font-weight:600;padding-top:5px;border-top:.5px solid var(--border)"
                         :style="(closeResult && closeResult.variance===0) ? 'color:var(--success)' : 'color:var(--danger)'">
                        <span x-text="!closeResult ? '' : (closeResult.variance===0 ? 'Balanced' : (closeResult.variance>0 ? 'Over by' : 'Short by'))"></span>
                        <span x-text="closeResult ? 'Rs. ' + Math.abs(closeResult.variance).toLocaleString() : ''"></span>
                    </div>
                </div>
                <button @click="window.location.href = dashboardUrl" style="width:100%;height:38px;background:var(--primary-soft);border:.5px solid var(--primary-border);border-radius:7px;color:var(--primary-text);font-size:13px;font-weight:600;cursor:pointer">Done</button>
            </div>
            </template>
        </div>
    </div>
    </template>

    {{-- Keyboard shortcuts popup --}}
    <template x-teleport="body">
    <div x-show="showShortcuts" x-cloak @keydown.escape.window="showShortcuts=false" @click.self="showShortcuts=false"
         style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:50">
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:12px;padding:18px;width:360px;max-height:88vh;overflow-y:auto">
            <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:10px;display:flex;align-items:center;gap:6px">
                <i class="ti ti-keyboard" style="color:var(--primary)"></i> Keyboard shortcuts
            </div>
            @foreach([
                ['F1','Show this help'],
                ['F2','Focus search'],
                ['F3','Focus cash received'],
                ['F4','Cash payment'],
                ['F5','Split / credit payment'],
                ['F6','Card payment'],
                ['F7','Set exact cash'],
                ['F8','Toggle full screen'],
                ['F9','Customer search / add'],
                ['Numpad + / −','Active item quantity'],
                ['↑ / ↓ + Enter','Pick a search suggestion'],
                ['Tab','Next count field (counter popups)'],
                ['Esc','Close a popup'],
            ] as [$k,$desc])
            <div class="sc-row"><span style="color:var(--text-2)">{{ $desc }}</span><span class="sc-key">{{ $k }}</span></div>
            @endforeach
            <button @click="showShortcuts=false" style="width:100%;height:36px;margin-top:14px;background:var(--surface-2);border:.5px solid var(--border);border-radius:6px;color:var(--text-2);font-size:12px;cursor:pointer">Close</button>
        </div>
    </div>
    </template>

    {{-- Calculator popup --}}
    <template x-teleport="body">
    <div x-show="showCalc" x-cloak @keydown.escape.window="showCalc=false" @click.self="showCalc=false"
         style="position:fixed;inset:0;background:var(--overlay);display:flex;align-items:center;justify-content:center;z-index:50">
        <div style="background:var(--surface);border:.5px solid var(--border);border-radius:12px;padding:16px;width:280px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <div style="font-size:13px;font-weight:600;color:var(--text);display:flex;align-items:center;gap:6px"><i class="ti ti-calculator" style="color:var(--primary)"></i> Calculator</div>
                <i class="ti ti-x" @click="showCalc=false" style="font-size:15px;color:var(--text-3);cursor:pointer"></i>
            </div>
            <div style="background:var(--bg);border:.5px solid var(--border);border-radius:8px;padding:8px 12px;margin-bottom:10px;text-align:right">
                <div style="font-size:11px;color:var(--text-5);min-height:14px" x-text="calcSub"></div>
                <div style="font-size:26px;color:var(--text);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" x-text="calcDisplay"></div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px">
                <button class="calc-key" @click="calcClear()" style="color:var(--danger)">C</button>
                <button class="calc-key" @click="calcBackspace()"><i class="ti ti-backspace"></i></button>
                <button class="calc-key op" @click="calcOperator('/')">÷</button>
                <button class="calc-key op" @click="calcOperator('*')">×</button>
                <button class="calc-key" @click="calcDigit('7')">7</button>
                <button class="calc-key" @click="calcDigit('8')">8</button>
                <button class="calc-key" @click="calcDigit('9')">9</button>
                <button class="calc-key op" @click="calcOperator('-')">−</button>
                <button class="calc-key" @click="calcDigit('4')">4</button>
                <button class="calc-key" @click="calcDigit('5')">5</button>
                <button class="calc-key" @click="calcDigit('6')">6</button>
                <button class="calc-key op" @click="calcOperator('+')">+</button>
                <button class="calc-key" @click="calcDigit('1')">1</button>
                <button class="calc-key" @click="calcDigit('2')">2</button>
                <button class="calc-key" @click="calcDigit('3')">3</button>
                <button class="calc-key op" style="grid-row:span 2" @click="calcEquals()">=</button>
                <button class="calc-key" style="grid-column:span 2" @click="calcDigit('0')">0</button>
                <button class="calc-key" @click="calcDot()">.</button>
            </div>
        </div>
    </div>
    </template>
</div>

@push('scripts')
<script>
window.__POS = {
    denoms: @json($denominations),
    hasCounter: {{ $counter ? 'true' : 'false' }},
    dashboardUrl: '{{ route('dashboard') }}',
    allowCreditNewCustomers: {{ \App\Models\Setting::get('allow_credit_new_customers') === '1' ? 'true' : 'false' }},
    counterOpen: {{ $openSession ? 'true' : 'false' }},
    openingBalance: {{ $openSession ? (float) $openSession->opening_balance : 0 }},
    cashSalesSoFar: {{ $openSession ? (float) ($openSession->cash_sales_so_far ?? 0) : 0 }},
    prevClose: @json($lastClose ? ['balance' => (float) $lastClose->closing_balance, 'denoms' => ($lastClose->closing_denoms ?? [])] : null),
};
</script>
<script>
// Unified POS screen component: products + cart in one Alpine scope
function posScreen() {
    return {
        // ── Product browsing ─────────────────────────────
        products: [],
        category: '',
        query: '',
        numpadTarget: 'cash',   // which field the on-screen numpad types into
        showSuggest: false,
        suggestIdx: 0,
        isFullscreen: false,

        init() {
            this.loadProducts('');
            this.loadHeldBills();

            window.addEventListener('keydown', (e) => {
                // Physical numpad + / - adjust the active cart item (when no popup is open)
                if (!this.anyModalOpen) {
                    if (e.code === 'NumpadAdd') { e.preventDefault(); this.bumpActive(1); return; }
                    if (e.code === 'NumpadSubtract') { e.preventDefault(); this.bumpActive(-1); return; }
                }
                // Function-key shortcuts for the most-used actions
                switch (e.key) {
                    case 'F1': e.preventDefault(); this.showShortcuts = !this.showShortcuts; break;
                    case 'F2': e.preventDefault(); document.getElementById('scan-input')?.focus(); break;
                    case 'F3': if (!this.anyModalOpen) { e.preventDefault(); document.getElementById('cash-input')?.focus(); } break;
                    case 'F4': if (!this.anyModalOpen) { e.preventDefault(); this.pay('cash'); } break;
                    case 'F5': if (!this.anyModalOpen) { e.preventDefault(); this.openSplit(); } break;
                    case 'F6': if (!this.anyModalOpen) { e.preventDefault(); this.pay('card'); } break;
                    case 'F7': if (!this.anyModalOpen) { e.preventDefault(); this.npExact(); document.getElementById('cash-input')?.focus(); } break;
                    case 'F8': e.preventDefault(); this.toggleFullscreen(); break;
                    case 'F9': if (!this.anyModalOpen) { e.preventDefault(); this.openCustomerSearch(); } break;
                }
            });

            // Keep state in sync if the user leaves fullscreen via Esc/F11
            document.addEventListener('fullscreenchange', () => {
                if (!document.fullscreenElement) { document.body.classList.remove('pos-fullscreen'); this.isFullscreen = false; }
            });

            // Auto-focus inside popups for a fast keyboard workflow
            this.$watch('showCardModal', v => { if (v) this.$nextTick(() => this.$refs.cardInput?.focus()); });
            this.$watch('showCashModal', v => { if (v) this.$nextTick(() => this.$refs.cashBtn?.focus()); });
            this.$watch('showSplitModal', v => { if (v) this.$nextTick(() => this.$refs.splitCardInput?.focus()); });
            this.$watch('showSaleModal', v => { if (v) this.$nextTick(() => this.$refs.printBtn?.focus()); });
            this.$watch('showOpenModal', v => { if (v) this.$nextTick(() => document.querySelector('.denom-open')?.focus()); });
            this.$watch('showCloseModal', v => { if (v && !this.closeResult) this.$nextTick(() => document.querySelector('.denom-close')?.focus()); });

            // Block POS use until a counter session is open
            if (this.hasCounter && !this.counterOpen) {
                this.$nextTick(() => this.openCounterPrompt());
            }
        },

        async loadProducts(q) {
            try {
                const res = await fetch(`/api/products/search?q=${encodeURIComponent(q || '')}&category=${this.category}`);
                const list = await res.json();
                // Don't show out-of-stock items on the POS grid.
                this.products = list.filter(p => Number(p.stock) > 0);
            } catch (e) {
                this.products = [];
            }
        },

        // called by the search/scan input (debounced)
        searchProducts(q) {
            this.loadProducts(q);
            this.showSuggest = !!q;
            this.suggestIdx = 0;
        },

        get suggestions() { return this.products.slice(0, 8); },

        moveSuggest(d) {
            const n = this.suggestions.length;
            if (!n) return;
            this.showSuggest = true;
            this.suggestIdx = (this.suggestIdx + d + n) % n;
        },

        chooseSuggest() {
            if (this.showSuggest && this.suggestions[this.suggestIdx]) {
                this.addToCart(this.suggestions[this.suggestIdx]);
                this.query = '';
                this.showSuggest = false;
                this.suggestIdx = 0;
            } else if (this.query) {
                this.handleScan(this.query);
                this.query = '';
            }
        },

        chooseSuggestAt(i) { this.suggestIdx = i; this.chooseSuggest(); },

        filterCategory(id, el) {
            this.category = id ?? '';
            document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
            el.classList.add('active');
            this.loadProducts(document.getElementById('scan-input').value);
        },

        // Enter on the scan bar: look up a barcode/name and add the first match
        handleScan(value) {
            if (!value) return;
            const code = value.trim();
            // Scale/weighed embedded barcodes use GS1 prefix "2" (typically 13 digits, but
            // the length is configurable) — resolve them via the barcode endpoint, which
            // returns the weighed quantity. Falls back to a normal search so ordinary
            // scans/typing are unaffected.
            if (/^2\d{11,13}$/.test(code)) {
                fetch(`/pos/products/barcode/${encodeURIComponent(code)}`)
                    .then(r => r.ok ? r.json() : Promise.reject())
                    .then(data => this.addScanned(data))
                    .catch(() => this.searchAdd(code));
                return;
            }
            this.searchAdd(code);
        },

        searchAdd(code) {
            fetch(`/api/products/search?q=${encodeURIComponent(code)}&category=`)
                .then(r => r.json())
                .then(list => {
                    if (list.length) this.addToCart(list[0]);
                    else alert('No product found for: ' + code);
                });
        },

        // Add a product returned from the barcode endpoint. Weighed items carry their
        // own quantity (one line per weigh-in), so they aren't merged like unit items.
        addScanned(data) {
            if (data && data.weighed) {
                if (this.wouldOversell(data.id, data.name, data.unit, Number(data.qty) || 0)) return;
                this.cart.push({
                    id: data.id, name: data.name, barcode: data.barcode,
                    price: data.price, tax_percent: data.tax_percent || 0,
                    unit: data.unit, qty: data.qty, weighed: true, stock: data.stock,
                });
                this.activeIdx = this.cart.length - 1;
                this.$nextTick(() => this.scrollToActive());
            } else {
                this.addToCart(data);
            }
        },

        cart: [],
        activeIdx: -1,          // cart item the +/- keys act on
        customer: null,
        cashStr: '0',
        couponCode: '',
        discountInput: '',
        discountMode: 'amount',   // 'amount' (Rs) or 'percent' (%)
        taxPercent: '',
        // popups
        showSaleModal: false,
        lastSale: null,
        creditBillPrinted: false,
        showCardModal: false,
        cardLast4: '',
        showCashModal: false,

        // ── Counter session ──────────────────────────────
        denoms: (window.__POS && window.__POS.denoms) || [5000,2000,1000,500,100,50,20,10,5,2,1],
        hasCounter: !!(window.__POS && window.__POS.hasCounter),
        dashboardUrl: (window.__POS && window.__POS.dashboardUrl) || '/dashboard',
        counterOpen: !!(window.__POS && window.__POS.counterOpen),
        openingBalance: (window.__POS && window.__POS.openingBalance) || 0,
        cashSalesSoFar: (window.__POS && window.__POS.cashSalesSoFar) || 0,
        prevClose: (window.__POS && window.__POS.prevClose) || null,
        showOpenModal: false,
        showCloseModal: false,
        openDenoms: {},
        closeDenoms: {},
        openError: '',
        closeError: '',
        closeResult: null,

        // ── Tools: shortcuts help + calculator ───────────
        showShortcuts: false,
        showCalc: false,
        calcDisplay: '0',
        calcAcc: null,
        calcOp: null,
        calcFresh: true,

        get cartCount() { return this.cart.reduce((a, i) => a + i.qty, 0); },
        get subtotal() { return this.cart.reduce((a, i) => a + i.price * i.qty, 0); },
        get discountValue() {
            const v = parseFloat(this.discountInput) || 0;
            const amt = this.discountMode === 'percent' ? this.subtotal * v / 100 : v;
            return Math.min(Math.max(0, Math.round(amt * 100) / 100), this.subtotal);
        },
        get tax() {
            const base = Math.max(0, this.subtotal - this.discountValue);
            return Math.round(base * (parseFloat(this.taxPercent) || 0) / 100 * 100) / 100;
        },
        get total() { return Math.max(0, Math.round((this.subtotal - this.discountValue + this.tax) * 100) / 100); },
        get cashNum() { return parseFloat(this.cashStr) || 0; },
        get cashDisplay() { return parseFloat(this.cashStr).toLocaleString(); },

        // ── Multi-price chooser ──
        showPriceModal: false,
        priceChooserProduct: null,
        priceChooserOptions: [],

        // ── Custom / temporary item ──
        showCustomModal: false,
        customName: '', customPrice: '', customQty: 1,
        openCustomItem() {
            this.customName = ''; this.customPrice = ''; this.customQty = 1;
            this.showCustomModal = true;
            this.$nextTick(() => this.$refs.customNameInput?.focus());
        },
        addCustomItem() {
            const name = (this.customName || '').trim();
            const price = parseFloat(this.customPrice);
            const qty = parseFloat(this.customQty) || 1;
            if (!name) { alert('Enter an item name.'); return; }
            if (!isFinite(price) || price < 0) { alert('Enter a valid price.'); return; }
            this.cart.push({ id: null, custom: true, name, price, qty, tax_percent: 0, unit: 'Item' });
            this.activeIdx = this.cart.length - 1;
            this.showCustomModal = false;
            this.$nextTick(() => this.scrollToActive());
        },

        // ── Split / credit payment (cash + card + credit) ──
        showSplitModal: false,
        splitCard: '', splitCash: '', splitLast4: '', splitCredit: '',
        allowCreditNewCustomers: !!(window.__POS && window.__POS.allowCreditNewCustomers),
        get splitCardNum() { return Math.max(0, parseFloat(this.splitCard) || 0); },
        get splitCashNum() { return Math.max(0, parseFloat(this.splitCash) || 0); },
        get splitCreditNum() { return Math.max(0, parseFloat(this.splitCredit) || 0); },
        // Cash needed = the bill minus what card and credit already cover.
        get splitCashNeeded() { return Math.max(0, Math.round((this.total - Math.min(this.splitCardNum, this.total) - Math.min(this.splitCreditNum, this.total)) * 100) / 100); },
        get splitChange() { return Math.max(0, Math.round((this.splitCashNum - this.splitCashNeeded) * 100) / 100); },
        // Is the selected customer allowed to take the credit portion?
        get creditCustomerOk() {
            if (this.splitCreditNum <= 0) return true;
            if (!this.customer) return false;
            if (!this.customer.credit_approved && !this.allowCreditNewCustomers) return false;
            if (!this.customer.nic) return false;
            return true;
        },
        get creditCustomerError() {
            if (!this.customer) return "Select a registered customer for credit — walk-in can't buy on credit.";
            if (!this.customer.credit_approved && !this.allowCreditNewCustomers) return 'This customer is not approved for credit.';
            if (!this.customer.nic) return "Add the customer's NIC before selling on credit.";
            return '';
        },
        get splitValid() {
            if (this.total <= 0) return false;
            if (this.splitCardNum + this.splitCreditNum > this.total + 1e-9) return false;
            if (this.splitCardNum > 0 && this.splitLast4.length !== 4) return false;
            if (this.splitCreditNum > 0 && !this.creditCustomerOk) return false;
            return this.splitCashNum + this.splitCardNum + this.splitCreditNum >= this.total - 1e-9;
        },
        get splitError() {
            if (this.splitCardNum + this.splitCreditNum > this.total + 1e-9) return 'Card + credit is more than the total.';
            if (this.splitCardNum > 0 && this.splitLast4.length !== 4) return 'Enter the card last 4 digits.';
            if (this.splitCreditNum > 0 && !this.creditCustomerOk) return this.creditCustomerError;
            if (this.splitCashNum + this.splitCardNum + this.splitCreditNum < this.total - 1e-9) return 'Cash + card + credit is less than the total.';
            return '';
        },
        openSplit() {
            if (this.cart.length === 0) { alert('Cart is empty!'); return; }
            if (this.hasCounter && !this.counterOpen) { this.openCounterPrompt(); return; }
            this.splitCard = ''; this.splitCash = ''; this.splitLast4 = ''; this.splitCredit = '';
            this.showSplitModal = true;
        },
        openCredit() {
            if (this.cart.length === 0) { alert('Cart is empty!'); return; }
            if (this.hasCounter && !this.counterOpen) { this.openCounterPrompt(); return; }
            if (!this.customer) { alert("Select a registered customer first — walk-in customers can't buy on credit."); this.openCustomerSearch(); return; }
            if (!this.customer.credit_approved && !this.allowCreditNewCustomers) { alert('This customer is not approved for credit.'); return; }
            if (!this.customer.nic) { alert("Add the customer's NIC before selling on credit."); return; }
            this.splitCard = ''; this.splitCash = ''; this.splitLast4 = '';
            this.splitCredit = this.total;      // whole bill on account by default
            this.showSplitModal = true;
        },
        confirmSplit() {
            if (!this.splitValid) return;
            this.showSplitModal = false;
            // Pure credit → 'credit'; anything with cash/card → 'mixed'.
            const method = (this.splitCreditNum > 0 && this.splitCashNum <= 0 && this.splitCardNum <= 0) ? 'credit' : 'mixed';
            this.processPayment(method);
        },

        // ── Credit signed-document evidence (webcam or phone QR) ──
        showEvidenceModal: false,
        evidenceTab: 'webcam',
        evidenceStream: null,
        evidenceBusy: false,
        evidenceMsg: '',
        evidenceDone: false,
        qrSvg: '', qrCode: '', qrLoading: false,
        evidencePoll: null,
        openEvidence() {
            if (!this.lastSale) return;
            this.showSaleModal = false;
            this.evidenceTab = 'webcam';
            this.evidenceMsg = ''; this.evidenceDone = false;
            this.qrSvg = ''; this.qrCode = '';
            this.showEvidenceModal = true;
            this.$nextTick(() => this.startWebcam());
        },
        async startWebcam() {
            this.stopWebcam();
            this.evidenceMsg = '';
            try {
                this.evidenceStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                const v = this.$refs.evidenceVideo;
                if (v) { v.srcObject = this.evidenceStream; await v.play().catch(() => {}); }
            } catch (e) {
                this.evidenceMsg = 'No webcam available — use your phone instead.';
                this.switchEvidenceTab('phone');
            }
        },
        stopWebcam() {
            if (this.evidenceStream) { this.evidenceStream.getTracks().forEach(t => t.stop()); this.evidenceStream = null; }
        },
        async captureWebcam() {
            const v = this.$refs.evidenceVideo;
            if (!v || !v.videoWidth) { this.evidenceMsg = 'Camera not ready yet.'; return; }
            this.evidenceBusy = true; this.evidenceMsg = 'Uploading…';
            try {
                const c = document.createElement('canvas');
                c.width = v.videoWidth; c.height = v.videoHeight;
                c.getContext('2d').drawImage(v, 0, 0);
                const blob = await new Promise(ok => c.toBlob(ok, 'image/webp', 0.85));
                const fd = new FormData();
                fd.append('photo', blob, 'signed.webp');
                fd.append('_token', this.csrf());
                const res = await fetch(`/pos/sale/${this.lastSale.sale_id}/credit-document`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: fd,
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) { this.evidenceDone = true; this.stopWebcam(); this.stopEvidencePoll(); }
                else { this.evidenceMsg = data.message || 'Upload failed. Try again.'; }
            } catch (e) { this.evidenceMsg = 'Upload failed. Try again.'; }
            this.evidenceBusy = false;
        },
        switchEvidenceTab(tab) {
            this.evidenceTab = tab;
            this.evidenceMsg = '';
            if (tab === 'webcam') { this.stopEvidencePoll(); this.startWebcam(); }
            else { this.stopWebcam(); this.loadQr(); this.startEvidencePoll(); }
        },
        async loadQr() {
            if (!this.lastSale) return;
            this.qrLoading = true; this.qrSvg = ''; this.qrCode = '';
            try {
                const res = await fetch(`/pos/sale/${this.lastSale.sale_id}/credit-upload-link`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.qrSvg = data.qr_svg || '';
                this.qrCode = data.code || '';
            } catch (e) { this.evidenceMsg = 'Could not create the upload link.'; }
            this.qrLoading = false;
        },
        startEvidencePoll() {
            this.stopEvidencePoll();
            this.evidencePoll = setInterval(async () => {
                if (!this.lastSale || this.evidenceDone) return;
                try {
                    const res = await fetch(`/pos/sale/${this.lastSale.sale_id}/credit-document`, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (data.attached) { this.evidenceDone = true; this.stopEvidencePoll(); this.stopWebcam(); }
                } catch (e) {}
            }, 3000);
        },
        stopEvidencePoll() { if (this.evidencePoll) { clearInterval(this.evidencePoll); this.evidencePoll = null; } },
        closeEvidence() { this.stopWebcam(); this.stopEvidencePoll(); this.showEvidenceModal = false; },
        // Go back to the success screen (to print/reprint) without dismissing the flow —
        // the signed copy is still required before the next sale.
        evBack() { this.stopWebcam(); this.stopEvidencePoll(); this.showEvidenceModal = false; this.showSaleModal = true; },

        // ── Held / parked bills ──
        heldBills: [],
        showHeldModal: false,
        csrf() { return document.querySelector('meta[name=csrf-token]').content; },

        async loadHeldBills() {
            try { const r = await fetch('/pos/held'); this.heldBills = await r.json(); }
            catch (e) { this.heldBills = []; }
        },
        openHeldBills() { this.loadHeldBills(); this.showHeldModal = true; },

        async holdBill() {
            if (this.cart.length === 0) { alert('Cart is empty.'); return; }
            const label = prompt('Label for this held bill (optional):', this.customer?.name || '');
            if (label === null) return;   // cancelled
            const payload = {
                cart: this.cart, customer: this.customer,
                discountInput: this.discountInput, discountMode: this.discountMode,
                taxPercent: this.taxPercent, couponCode: this.couponCode,
            };
            try {
                const res = await fetch('/pos/hold', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                    body: JSON.stringify({ label: label.trim(), item_count: this.cart.length, total: this.total, payload }),
                });
                if (res.ok) { this.clearCart(); await this.loadHeldBills(); }
                else alert('Could not hold the bill.');
            } catch (e) { alert('Could not hold the bill.'); }
        },

        async resumeBill(id) {
            if (this.cart.length > 0 && !confirm('The current cart will be replaced — hold it first if you need it. Continue?')) return;
            try {
                const res = await fetch(`/pos/held/${id}/resume`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                });
                const data = await res.json();
                if (data.success && data.payload) {
                    const p = data.payload;
                    this.cart = Array.isArray(p.cart) ? p.cart : [];
                    this.customer = p.customer || null;
                    this.discountInput = p.discountInput || '';
                    this.discountMode = p.discountMode || 'amount';
                    this.taxPercent = p.taxPercent || '';
                    this.couponCode = p.couponCode || '';
                    this.activeIdx = this.cart.length - 1;
                }
                this.showHeldModal = false;
                await this.loadHeldBills();
            } catch (e) { alert('Could not resume the bill.'); }
        },

        async discardBill(id) {
            if (!confirm('Discard this held bill?')) return;
            try {
                await fetch(`/pos/held/${id}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() } });
                await this.loadHeldBills();
            } catch (e) {}
        },

        // ── Inline unit-price override (per cart line) ──
        editPriceIdx: -1,
        startEditPrice(idx) {
            this.editPriceIdx = idx;
            this.$nextTick(() => { const el = document.getElementById('price-input-' + idx); if (el) { el.focus(); el.select(); } });
        },
        commitPrice(idx) {
            if (this.cart[idx]) {
                let v = parseFloat(this.cart[idx].price);
                this.cart[idx].price = (isFinite(v) && v >= 0) ? v : 0;
            }
            this.editPriceIdx = -1;
        },

        addToCart(product) {
            // Non-weighed products can have several in-stock prices (same SKU/barcode) — let the cashier pick.
            const opts = (product && !product.is_weighed && Array.isArray(product.price_options)) ? product.price_options : [];
            if (opts.length > 1) {
                this.priceChooserProduct = product;
                this.priceChooserOptions = opts;
                this.showPriceModal = true;
                return;
            }
            this.addLine(product, opts.length === 1 ? opts[0] : product.price);
        },

        pickPrice(price) {
            this.showPriceModal = false;
            if (this.priceChooserProduct) this.addLine(this.priceChooserProduct, price);
            this.priceChooserProduct = null;
        },

        // ── Stock guard (prevent overselling) ──
        stockFor(id) {
            const p = this.products.find(x => x.id === id);
            if (p && p.stock != null) return Number(p.stock);
            const line = this.cart.find(x => x.id === id && x.stock != null);
            return line ? Number(line.stock) : Infinity;
        },
        // Total quantity of this product already in the cart (across all price lines).
        inCartQty(id) {
            return this.cart.filter(x => x.id === id && !x.custom)
                            .reduce((a, i) => a + (Number(i.qty) || 0), 0);
        },
        // True if `add` more of this product would exceed available stock (and warns).
        wouldOversell(id, name, unit, add) {
            const stock = this.stockFor(id);
            if (this.inCartQty(id) + add > stock + 1e-9) {
                alert(`Only ${stock} ${unit || ''} of "${name}" in stock.`.replace(/\s+/g, ' ').trim());
                return true;
            }
            return false;
        },

        // Add a line at a specific price; lines of the same product at different prices stay separate.
        addLine(product, price) {
            if (this.wouldOversell(product.id, product.name, product.unit, 1)) return;
            const p = Number(price);
            const i = this.cart.findIndex(x => x.id === product.id && Number(x.price) === p && !x.weighed);
            if (i !== -1) {
                this.cart[i].qty++;
                this.activeIdx = i;
            } else {
                this.cart.push({ ...product, price: p, qty: 1 });
                this.activeIdx = this.cart.length - 1;
            }
            this.$nextTick(() => this.scrollToActive());
        },

        // Adjust the active item's quantity; remove it when it hits 0.
        bumpActive(d) {
            if (this.activeIdx < 0 || !this.cart[this.activeIdx]) return;
            const line = this.cart[this.activeIdx];
            if (d > 0 && !line.custom && this.wouldOversell(line.id, line.name, line.unit, d)) return;
            this.cart[this.activeIdx].qty += d;
            if (this.cart[this.activeIdx].qty <= 0) {
                this.cart.splice(this.activeIdx, 1);
                this.activeIdx = Math.min(this.activeIdx, this.cart.length - 1);
            }
            if (this.activeIdx >= 0) this.$nextTick(() => this.scrollToActive());
        },

        scrollToActive() {
            const el = document.getElementById('cart-item-' + this.activeIdx);
            if (el) el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        },

        changeQty(idx, d) {
            const line = this.cart[idx];
            if (d > 0 && line && !line.custom && this.wouldOversell(line.id, line.name, line.unit, d)) return;
            this.activeIdx = idx;
            this.cart[idx].qty += d;
            if (this.cart[idx].qty <= 0) {
                this.cart.splice(idx, 1);
                this.activeIdx = Math.min(idx, this.cart.length - 1);
            }
        },

        removeItem(idx) {
            this.cart.splice(idx, 1);
            if (this.activeIdx >= this.cart.length) this.activeIdx = this.cart.length - 1;
        },
        clearCart() { this.cart = []; this.activeIdx = -1; this.discountInput = ''; this.discountMode = 'amount'; this.taxPercent = ''; this.couponCode = ''; this.cashStr = '0'; },

        np(v) {
            if (this.numpadTarget === 'search') {
                this.query += v;
                this.searchProducts(this.query);
                return;
            }
            if (this.numpadTarget === 'discount') { this.discountInput += v; return; }
            if (this.numpadTarget === 'tax') { this.taxPercent += v; return; }
            if (this.cashStr === '0' && v !== '.') this.cashStr = v;
            else this.cashStr += v;
        },
        npDel() {
            if (this.numpadTarget === 'search') {
                this.query = this.query.slice(0, -1);
                this.searchProducts(this.query);
                return;
            }
            if (this.numpadTarget === 'discount') { this.discountInput = this.discountInput.slice(0, -1); return; }
            if (this.numpadTarget === 'tax') { this.taxPercent = this.taxPercent.slice(0, -1); return; }
            this.cashStr = this.cashStr.slice(0, -1) || '0';
        },
        npExact() { this.cashStr = this.total.toFixed(2); },

        // ── Customer search + registration ───────────────
        showCustomerModal: false,
        customerQuery: '',
        customerResults: [],
        customerSearching: false,
        newCustomer: { name: '', phone: '', email: '', nic: '', address: '' },
        customerError: '',

        openCustomerSearch() {
            this.customerError = '';
            this.customerQuery = '';
            this.customerResults = [];
            this.newCustomer = { name: '', phone: '', email: '', nic: '', address: '' };
            this.showCustomerModal = true;
        },

        async searchCustomers() {
            const q = this.customerQuery.trim();
            if (!q) { this.customerResults = []; return; }
            this.customerSearching = true;
            try {
                const res = await fetch(`/api/customers/search?q=${encodeURIComponent(q)}`);
                this.customerResults = await res.json();
            } catch (e) {
                this.customerResults = [];
            }
            this.customerSearching = false;
        },

        selectCustomer(c) {
            this.customer = c;
            this.showCustomerModal = false;
        },

        clearCustomer() { this.customer = null; },

        async saveCustomer() {
            if (!this.newCustomer.name.trim()) { this.customerError = 'Name is required.'; return; }
            try {
                const res = await fetch('/api/customers', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify(this.newCustomer),
                });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    this.customerError = err.message || 'Could not save customer.';
                    return;
                }
                this.customer = await res.json();
                this.showCustomerModal = false;
            } catch (e) {
                this.customerError = 'Network error. Try again.';
            }
        },

        openCouponModal() {
            const code = prompt('Enter coupon code:');
            if (!code) return;
            fetch(`/api/coupons/validate?code=${code}&amount=${this.subtotal}`)
                .then(r => r.json())
                .then(data => {
                    if (data.valid) {
                        this.couponCode = data.code;
                        this.discountMode = 'amount';
                        this.discountInput = String(data.discount);
                        alert(`Coupon applied! Discount: Rs. ${data.discount.toLocaleString()}`);
                    } else {
                        alert('Invalid or expired coupon.');
                    }
                });
        },

        pay(method) {
            if (this.cart.length === 0) { alert('Cart is empty!'); return; }
            if (this.hasCounter && !this.counterOpen) { this.openCounterPrompt(); return; }
            if (method === 'cash') {
                if (this.cashNum < this.total) { alert('Insufficient cash amount!'); return; }
                this.showCashModal = true;   // confirm before completing
            } else if (method === 'card') {
                this.cardLast4 = '';
                this.showCardModal = true;   // capture last 4 digits first
            }
        },

        confirmCash() {
            this.showCashModal = false;
            this.processPayment('cash');
        },

        confirmCard() {
            if (!/^\d{4}$/.test(this.cardLast4)) return;
            this.showCardModal = false;
            this.processPayment('card');
        },

        async processPayment(method) {
            const payload = {
                items: this.cart.map(i => ({
                    id: i.id,
                    name: i.name,
                    qty: i.qty,
                    price: i.price,
                    tax_percent: i.tax_percent ?? 0,
                })),
                customer_id: this.customer?.id ?? null,
                coupon_code: this.couponCode,
                subtotal: this.subtotal,
                discount_amount: this.discountValue,
                tax_amount: this.tax,
                payment_method: method,
                paid_amount: method === 'cash' ? this.cashNum
                           : (method === 'mixed' ? (this.splitCashNum + this.splitCardNum)
                           : (method === 'credit' ? 0 : this.total)),
                card_last4: method === 'card' ? this.cardLast4 : (method === 'mixed' ? this.splitLast4 : null),
                cash_amount: method === 'mixed' ? this.splitCashNum : 0,
                card_amount: method === 'mixed' ? this.splitCardNum : 0,
                credit_amount: method === 'mixed' ? this.splitCreditNum : (method === 'credit' ? this.total : 0),
                _token: document.querySelector('meta[name=csrf-token]').content,
            };

            try {
                const res = await fetch('/pos/sale', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();

                if (data.success) {
                    this.lastSale = data;        // { sale_id, invoice_no, total, change, cash_amount, is_credit, balance_due }
                    this.creditBillPrinted = false;
                    this.cashSalesSoFar += Number(data.cash_amount) || 0;   // only the cash portion
                    this.showSaleModal = true;
                    this.clearCart();
                    this.loadProducts(this.query);   // refresh stock counts after the sale
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (e) {
                alert('Payment failed. Please try again.');
            }
        },

        printReceipt() {
            if (this.lastSale) {
                window.open(`/pos/receipt/${this.lastSale.sale_id}`, '_blank', 'width=380,height=600');
            }
            this.showSaleModal = false;
        },
        // Credit bills: print but KEEP the success modal open — the signed copy is still required.
        printCreditBill() {
            if (this.lastSale) window.open(`/pos/receipt/${this.lastSale.sale_id}`, '_blank', 'width=380,height=600');
            this.creditBillPrinted = true;
        },

        get anyModalOpen() { return this.showCustomerModal || this.showSaleModal || this.showCardModal || this.showCashModal || this.showSplitModal || this.showEvidenceModal || this.showOpenModal || this.showCloseModal || this.showCalc || this.showShortcuts; },

        toggleFullscreen() {
            const el = document.documentElement;
            if (!document.fullscreenElement) {
                if (el.requestFullscreen) el.requestFullscreen().catch(() => {});
                document.body.classList.add('pos-fullscreen');
                this.isFullscreen = true;
            } else {
                if (document.exitFullscreen) document.exitFullscreen().catch(() => {});
                document.body.classList.remove('pos-fullscreen');
                this.isFullscreen = false;
            }
        },

        // ── Counter open / close ─────────────────────────
        denomsTotal(obj) {
            return this.denoms.reduce((s, d) => s + d * (parseInt(obj[d]) || 0), 0);
        },
        get openTotal() { return this.denomsTotal(this.openDenoms); },
        get closeTotal() { return this.denomsTotal(this.closeDenoms); },
        get closeExpected() { return this.openingBalance + this.cashSalesSoFar; },
        get closeVariance() { return Math.round((this.closeTotal - this.closeExpected) * 100) / 100; },

        openCounterPrompt() {
            this.openError = '';
            this.openDenoms = {};
            this.denoms.forEach(d => { this.openDenoms[d] = (this.prevClose && this.prevClose.denoms && this.prevClose.denoms[d]) || 0; });
            this.showOpenModal = true;
        },

        closeCounterPrompt() {
            this.closeError = '';
            this.closeResult = null;
            this.closeDenoms = {};
            this.denoms.forEach(d => { this.closeDenoms[d] = 0; });
            this.showCloseModal = true;
        },

        async submitOpen() {
            this.openError = '';
            try {
                const res = await fetch('/pos/counter/open', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ denoms: this.openDenoms }),
                });
                const data = await res.json();
                if (data.success) {
                    this.counterOpen = true;
                    this.openingBalance = data.opening;
                    this.cashSalesSoFar = 0;
                    this.showOpenModal = false;
                } else { this.openError = data.message || 'Could not open counter.'; }
            } catch (e) { this.openError = 'Could not open counter. Try again.'; }
        },

        async submitClose() {
            this.closeError = '';
            try {
                const res = await fetch('/pos/counter/close', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ denoms: this.closeDenoms }),
                });
                const data = await res.json();
                if (data.success) {
                    this.counterOpen = false;
                    this.prevClose = { balance: data.counted, denoms: { ...this.closeDenoms } };
                    this.closeResult = data;   // show the result view; "Done" returns to dashboard
                } else { this.closeError = data.message || 'Could not close counter.'; }
            } catch (e) { this.closeError = 'Could not close counter. Try again.'; }
        },

        // ── Calculator ───────────────────────────────────
        get calcSub() {
            if (this.calcAcc === null) return '';
            const sym = { '+': '+', '-': '−', '*': '×', '/': '÷' }[this.calcOp] || '';
            return this.calcAcc + ' ' + sym;
        },
        calcDigit(d) {
            if (this.calcFresh) { this.calcDisplay = d; this.calcFresh = false; }
            else { this.calcDisplay = this.calcDisplay === '0' ? d : this.calcDisplay + d; }
        },
        calcDot() {
            if (this.calcFresh) { this.calcDisplay = '0.'; this.calcFresh = false; }
            else if (!this.calcDisplay.includes('.')) { this.calcDisplay += '.'; }
        },
        calcOperator(op) {
            if (this.calcOp !== null && !this.calcFresh) this.calcEquals();
            this.calcAcc = parseFloat(this.calcDisplay) || 0;
            this.calcOp = op;
            this.calcFresh = true;
        },
        calcEquals() {
            if (this.calcOp === null) return;
            const a = this.calcAcc, b = parseFloat(this.calcDisplay) || 0;
            let r = 0;
            if (this.calcOp === '+') r = a + b;
            else if (this.calcOp === '-') r = a - b;
            else if (this.calcOp === '*') r = a * b;
            else if (this.calcOp === '/') r = b === 0 ? 0 : a / b;
            this.calcDisplay = String(Math.round(r * 1e6) / 1e6);
            this.calcAcc = null;
            this.calcOp = null;
            this.calcFresh = true;
        },
        calcClear() { this.calcDisplay = '0'; this.calcAcc = null; this.calcOp = null; this.calcFresh = true; },
        calcBackspace() {
            if (this.calcFresh) return;
            this.calcDisplay = this.calcDisplay.length > 1 ? this.calcDisplay.slice(0, -1) : '0';
            if (this.calcDisplay === '0') this.calcFresh = true;
        },
    };
}
</script>
@endpush
@endsection
