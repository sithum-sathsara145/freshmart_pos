{{-- resources/views/pos/index.blade.php --}}
@extends('layouts.app')

@section('title', 'POS — FreshMart')

@push('styles')
<style>
.pos-wrap{display:grid;grid-template-columns:1fr 340px;height:calc(100vh - 56px);background:#0f1117}
.product-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;padding:12px;overflow-y:auto;align-content:start}
.prod-card{background:#161821;border:0.5px solid #2a2d3a;border-radius:8px;padding:10px;cursor:pointer;transition:border-color .12s}
.prod-card:hover{border-color:#818cf8}
.prod-img{height:56px;background:#1e2130;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:8px}
.prod-name{font-size:12px;color:#e2e8f0;font-weight:500;margin-bottom:3px;line-height:1.3}
.prod-price{font-size:13px;color:#a5b4fc;font-weight:500}
.prod-stock{font-size:10px;color:#64748b}
.cart-panel{background:#161821;border-left:0.5px solid #2a2d3a;display:flex;flex-direction:column;min-height:0;overflow:hidden}
.cart-list{flex:1;overflow-y:auto;padding:8px 12px;min-height:0}
.cart-fixed{flex-shrink:0}
[x-cloak]{display:none!important}
.cust-input{width:100%;background:#0f1117;border:.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:12px;padding:8px 10px;outline:none;margin-bottom:8px;font-family:inherit}
.amt-input{width:92px;background:#0f1117;border:.5px solid #2a2d3a;border-radius:5px;color:#e2e8f0;font-size:12px;padding:4px 8px;text-align:right;outline:none;font-family:inherit}
input[type=number]::-webkit-inner-spin-button,input[type=number]::-webkit-outer-spin-button{-webkit-appearance:none;margin:0}
input[type=number]{-moz-appearance:textfield}
.ci{padding:8px 0;border-bottom:0.5px solid #1a1d2a}
.ci:last-child{border-bottom:none}
.ci-name{font-size:12px;color:#e2e8f0;font-weight:500}
.ci-meta{font-size:11px;color:#64748b;margin-top:1px}
.ci-row{display:flex;align-items:center;justify-content:space-between;margin-top:5px}
.qty-btn{width:22px;height:22px;background:#252840;border:0.5px solid #2a2d3a;border-radius:4px;color:#94a3b8;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px}
.np-key{height:36px;background:#1e2130;border:0.5px solid #2a2d3a;border-radius:6px;color:#e2e8f0;font-size:13px;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .1s}
.np-key:hover{background:#252840}
.np-key.action{background:#312e81;color:#a5b4fc;border-color:#534AB7}
.pay-btn{height:40px;border-radius:7px;font-size:13px;font-weight:500;cursor:pointer;border:none;display:flex;align-items:center;justify-content:center;gap:6px;transition:opacity .15s}
.pay-btn:hover{opacity:.85}
.cats{display:flex;gap:6px;padding:10px 12px;border-bottom:0.5px solid #2a2d3a;overflow-x:auto}
.cat-pill{padding:4px 14px;border-radius:20px;font-size:12px;font-weight:500;cursor:pointer;border:0.5px solid #2a2d3a;color:#94a3b8;background:#161821;white-space:nowrap}
.cat-pill.active{background:#312e81;color:#a5b4fc;border-color:#534AB7}
.scan-bar{height:48px;background:#161821;border-bottom:0.5px solid #2a2d3a;display:flex;align-items:center;gap:8px;padding:0 12px}
.scan-input{flex:1;background:#0f1117;border:0.5px solid #2a2d3a;border-radius:6px;padding:0 10px;height:34px;color:#e2e8f0;font-size:13px;outline:none;font-family:inherit}
.scan-input:focus{border-color:#818cf8}
</style>
@endpush

@section('content')
<div class="pos-wrap" x-data="posScreen()">
    {{-- LEFT: Products --}}
    <div style="display:flex;flex-direction:column;overflow:hidden">
        {{-- Scan bar --}}
        <div class="scan-bar">
            <i class="ti ti-scan" style="font-size:16px;color:#64748b"></i>
            <input class="scan-input" id="scan-input" x-model="query" autofocus
                   @focus="numpadTarget='search'"
                   @keyup.enter="handleScan(query); query=''"
                   @input.debounce.300ms="searchProducts(query)"
                   placeholder="Scan barcode or search product...">
            <button class="np-key action" style="width:100px;height:34px;font-size:12px"
                    @click="openCouponModal()">
                <i class="ti ti-tag" style="font-size:13px"></i> Coupon
            </button>
            <button class="np-key" style="width:110px;height:34px;font-size:12px"
                    @click="openCustomerSearch()">
                <i class="ti ti-user" style="font-size:13px"></i>
                <span x-text="customer ? customer.name.split(' ')[0] : 'Customer'"></span>
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
                <div class="prod-card" @click="addToCart(p)">
                    <div class="prod-img" x-text="p.emoji ?? '📦'"></div>
                    <div class="prod-name" x-text="p.name"></div>
                    <div class="prod-price" x-text="'Rs. ' + parseFloat(p.price).toLocaleString()"></div>
                    <div class="prod-stock" x-text="'Stock: ' + p.stock"></div>
                </div>
            </template>
            <template x-if="products.length === 0">
                <div style="grid-column:span 3;text-align:center;color:#4a5568;padding:32px;font-size:13px">
                    <i class="ti ti-search" style="font-size:28px;display:block;margin-bottom:8px"></i>
                    No products found
                </div>
            </template>
        </div>
    </div>

    {{-- RIGHT: Cart + numpad --}}
    <div class="cart-panel">

        {{-- Cart header --}}
        <div class="cart-fixed" style="padding:10px 12px;border-bottom:0.5px solid #2a2d3a">
            <div style="display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:13px;font-weight:500;color:#e2e8f0">
                    <i class="ti ti-shopping-cart" style="color:#818cf8;margin-right:4px"></i>
                    Cart <span style="font-size:11px;color:#64748b" x-text="'(' + cartCount + ' items)'"></span>
                </span>
                <span style="font-size:11px;color:#ef4444;cursor:pointer" @click="clearCart()">
                    <i class="ti ti-trash" style="font-size:12px"></i> Clear
                </span>
            </div>
            {{-- Customer row --}}
            <div style="display:flex;align-items:center;gap:6px;margin-top:8px;background:#0f1117;border:0.5px solid #2a2d3a;border-radius:6px;padding:5px 9px">
                <i class="ti ti-user" style="font-size:12px;color:#64748b"></i>
                <template x-if="!customer">
                    <div style="flex:1;display:flex;align-items:center;gap:6px;cursor:pointer" @click="openCustomerSearch()">
                        <span style="font-size:11px;color:#64748b;flex:1">Walk-in — tap to search / add</span>
                        <i class="ti ti-search" style="font-size:12px;color:#818cf8"></i>
                    </div>
                </template>
                <template x-if="customer">
                    <div style="flex:1;display:flex;align-items:center;gap:6px">
                        <span style="font-size:11px;color:#e2e8f0;flex:1" x-text="customer.name + (customer.phone ? ' · ' + customer.phone : '')"></span>
                        <i class="ti ti-x" style="font-size:13px;color:#ef4444;cursor:pointer" @click="clearCustomer()" title="Remove customer"></i>
                    </div>
                </template>
            </div>
        </div>

        {{-- Cart items --}}
        <div class="cart-list">
            <template x-if="cart.length === 0">
                <div style="text-align:center;color:#4a5568;font-size:12px;padding:24px 0">
                    <i class="ti ti-shopping-cart" style="font-size:28px;display:block;margin-bottom:8px"></i>
                    Cart is empty
                </div>
            </template>
            <template x-for="(item, idx) in cart" :key="idx">
                <div class="ci">
                    <div style="display:flex;justify-content:space-between;align-items:start">
                        <div class="ci-name" x-text="item.name"></div>
                        <i class="ti ti-x" style="font-size:13px;color:#ef4444;cursor:pointer;margin-left:6px"
                           @click="removeItem(idx)"></i>
                    </div>
                    <div class="ci-meta" x-text="'Rs. ' + parseFloat(item.price).toLocaleString() + ' each'"></div>
                    <div class="ci-row">
                        <div style="display:flex;align-items:center;gap:5px">
                            <div class="qty-btn" @click="changeQty(idx, -1)">−</div>
                            <span style="font-size:12px;color:#e2e8f0;min-width:20px;text-align:center" x-text="item.qty"></span>
                            <div class="qty-btn" @click="changeQty(idx, 1)">+</div>
                        </div>
                        <span style="font-size:13px;color:#a5b4fc;font-weight:500"
                              x-text="'Rs. ' + (item.price * item.qty).toLocaleString()"></span>
                    </div>
                </div>
            </template>
        </div>

        {{-- Totals --}}
        <div class="cart-fixed" style="padding:10px 12px;border-top:0.5px solid #2a2d3a">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:#94a3b8;margin-bottom:5px">
                <span>Subtotal</span><span x-text="'Rs. ' + subtotal.toLocaleString()"></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#94a3b8;margin-bottom:5px">
                <span>Discount <span style="color:#4ade80" x-show="couponCode" x-text="couponCode ? '(' + couponCode + ')' : ''"></span></span>
                <div style="display:flex;align-items:center;gap:5px">
                    <button type="button" @click="discountMode = discountMode === 'amount' ? 'percent' : 'amount'"
                            style="width:34px;height:26px;background:#1e2130;border:.5px solid #534AB7;border-radius:5px;color:#a5b4fc;font-size:11px;font-weight:600;cursor:pointer"
                            x-text="discountMode === 'amount' ? 'Rs' : '%'"></button>
                    <input type="text" inputmode="decimal" x-model="discountInput" placeholder="0" class="amt-input"
                           @focus="numpadTarget='discount'"
                           @input="discountInput = discountInput.replace(/[^0-9.]/g,'').replace(/(\..*)\./g,'$1')">
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:11px;color:#64748b;margin-bottom:3px" x-show="discountValue > 0">
                <span>Discount applied</span><span x-text="'- Rs. ' + discountValue.toLocaleString()"></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#94a3b8;margin-bottom:5px">
                <span>Tax (%)</span>
                <input type="text" inputmode="decimal" x-model="taxPercent" placeholder="0" class="amt-input"
                       @focus="numpadTarget='tax'"
                       @input="taxPercent = taxPercent.replace(/[^0-9.]/g,'').replace(/(\..*)\./g,'$1')">
            </div>
            <div style="display:flex;justify-content:space-between;font-size:11px;color:#64748b;margin-bottom:3px" x-show="tax > 0">
                <span>Tax amount</span><span x-text="'Rs. ' + tax.toLocaleString()"></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:14px;color:#e2e8f0;font-weight:500;padding-top:6px;border-top:0.5px solid #2a2d3a;margin-top:4px">
                <span>Total</span><span x-text="'Rs. ' + total.toLocaleString()"></span>
            </div>
        </div>

        {{-- Numpad --}}
        <div class="cart-fixed" style="padding:8px 12px;border-top:0.5px solid #2a2d3a">
            <div style="background:#0f1117;border:0.5px solid #2a2d3a;border-radius:6px;padding:6px 10px;margin-bottom:8px">
                <div style="font-size:10px;color:#64748b">Cash received</div>
                <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px">
                    <span style="font-size:14px;color:#64748b">Rs.</span>
                    <input type="text" inputmode="decimal" x-model="cashStr"
                           @focus="numpadTarget='cash'; $event.target.select()"
                           @input="cashStr = cashStr.replace(/[^0-9.]/g,'').replace(/(\..*)\./g,'$1')"
                           style="width:130px;background:transparent;border:none;outline:none;color:#e2e8f0;font-size:20px;font-weight:500;text-align:right;font-family:inherit;padding:0">
                </div>
                <div style="font-size:11px;color:#4ade80;text-align:right" x-show="cashNum >= total && cart.length > 0"
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
                <div class="np-key action" @click="npExact()">Exact</div>
                <div class="np-key" @click="np('1')">1</div>
                <div class="np-key" @click="np('2')">2</div>
                <div class="np-key" @click="np('3')">3</div>
                <div class="np-key" @click="np('0')">0</div>
                <div class="np-key" @click="np('00')" style="grid-column:span 2">00</div>
                <div class="np-key" @click="np('.')">.</div>
                <div class="np-key" @click="np('000')">000</div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                <button class="pay-btn" style="background:#1D9E75;color:#fff" @click="pay('cash')">
                    <i class="ti ti-cash"></i> Cash
                </button>
                <button class="pay-btn" style="background:#534AB7;color:#fff" @click="pay('card')">
                    <i class="ti ti-credit-card"></i> Card
                </button>
            </div>
        </div>
    </div>

    {{-- New customer modal --}}
    <div x-show="showCustomerModal" x-cloak @keydown.escape.window="showCustomerModal=false" @click.self="showCustomerModal=false"
         style="position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;z-index:50">
        <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:10px;padding:18px;width:330px">
            <div style="font-size:14px;font-weight:600;color:#e2e8f0;margin-bottom:12px;display:flex;align-items:center;gap:6px">
                <i class="ti ti-user" style="color:#818cf8"></i> Customer
            </div>

            {{-- Search existing --}}
            <input class="cust-input" x-model="customerQuery" @input.debounce.300ms="searchCustomers()" placeholder="Search name or phone...">
            <div x-show="customerResults.length" x-cloak style="max-height:150px;overflow-y:auto;border:.5px solid #2a2d3a;border-radius:6px;margin-bottom:8px">
                <template x-for="c in customerResults" :key="c.id">
                    <div @click="selectCustomer(c)" @mouseover="$el.style.background='#1e2130'" @mouseout="$el.style.background='transparent'"
                         style="display:flex;justify-content:space-between;padding:7px 10px;cursor:pointer;border-bottom:.5px solid #1a1d2a;font-size:12px">
                        <span style="color:#e2e8f0" x-text="c.name"></span>
                        <span style="color:#64748b" x-text="c.phone || ''"></span>
                    </div>
                </template>
            </div>
            <div x-show="customerQuery && !customerResults.length && !customerSearching" x-cloak style="text-align:center;color:#64748b;font-size:11px;margin-bottom:8px">No matches — register below</div>

            <div style="display:flex;align-items:center;gap:8px;margin:10px 0">
                <div style="flex:1;height:1px;background:#2a2d3a"></div>
                <span style="font-size:10px;color:#475569;letter-spacing:.5px">OR REGISTER NEW</span>
                <div style="flex:1;height:1px;background:#2a2d3a"></div>
            </div>

            <input class="cust-input" x-model="newCustomer.name" placeholder="Full name *" @keydown.enter="saveCustomer()">
            <input class="cust-input" x-model="newCustomer.phone" placeholder="Phone" @keydown.enter="saveCustomer()">
            <input class="cust-input" type="email" x-model="newCustomer.email" placeholder="Email (optional)" @keydown.enter="saveCustomer()">
            <div x-show="customerError" x-cloak x-text="customerError" style="color:#f87171;font-size:11px;margin-bottom:8px"></div>
            <div style="display:flex;gap:8px;margin-top:4px">
                <button @click="showCustomerModal=false" style="flex:1;height:36px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;cursor:pointer">Cancel</button>
                <button @click="saveCustomer()" style="flex:1;height:36px;background:#14532d;border:.5px solid #166534;border-radius:6px;color:#4ade80;font-size:12px;font-weight:500;cursor:pointer">Save &amp; select</button>
            </div>
        </div>
    </div>

    {{-- Sale success popup --}}
    <div x-show="showSaleModal" x-cloak @keydown.escape.window="showSaleModal=false"
         style="position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;z-index:50">
        <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:12px;padding:22px;width:320px;text-align:center">
            <div style="width:52px;height:52px;border-radius:50%;background:#14532d;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
                <i class="ti ti-check" style="font-size:28px;color:#4ade80"></i>
            </div>
            <div style="font-size:15px;font-weight:600;color:#e2e8f0;margin-bottom:4px">Payment successful</div>
            <div style="font-size:12px;color:#64748b;margin-bottom:14px" x-text="lastSale ? 'Invoice ' + lastSale.invoice_no : ''"></div>
            <div style="background:#0f1117;border:.5px solid #2a2d3a;border-radius:8px;padding:12px;margin-bottom:16px">
                <div style="display:flex;justify-content:space-between;font-size:13px;color:#e2e8f0;margin-bottom:4px">
                    <span>Total</span><span x-text="lastSale ? 'Rs. ' + Number(lastSale.total).toLocaleString() : ''"></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:13px;color:#4ade80" x-show="lastSale && lastSale.change > 0">
                    <span>Change</span><span x-text="lastSale ? 'Rs. ' + Number(lastSale.change).toLocaleString() : ''"></span>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <button @click="showSaleModal=false" style="flex:1;height:38px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:7px;color:#94a3b8;font-size:13px;cursor:pointer">Close</button>
                <button @click="printReceipt()" style="flex:1;height:38px;background:#312e81;border:.5px solid #534AB7;border-radius:7px;color:#a5b4fc;font-size:13px;font-weight:500;cursor:pointer">Next <i class="ti ti-arrow-right" style="font-size:13px"></i></button>
            </div>
        </div>
    </div>

    {{-- Card payment popup --}}
    <div x-show="showCardModal" x-cloak @keydown.escape.window="showCardModal=false" @click.self="showCardModal=false"
         style="position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;z-index:50">
        <div style="background:#161821;border:.5px solid #2a2d3a;border-radius:12px;padding:20px;width:300px">
            <div style="font-size:14px;font-weight:600;color:#e2e8f0;margin-bottom:4px;display:flex;align-items:center;gap:6px">
                <i class="ti ti-credit-card" style="color:#818cf8"></i> Card payment
            </div>
            <div style="font-size:11px;color:#64748b;margin-bottom:12px" x-text="'Amount: Rs. ' + total.toLocaleString()"></div>
            <label style="font-size:11px;color:#64748b">Last 4 digits of card *</label>
            <input x-model="cardLast4" inputmode="numeric" maxlength="4" placeholder="1234" class="cust-input"
                   style="margin-top:4px;text-align:center;letter-spacing:5px;font-size:16px"
                   @input="cardLast4 = cardLast4.replace(/\D/g,'').slice(0,4)" @keydown.enter="confirmCard()">
            <div style="display:flex;gap:8px;margin-top:4px">
                <button @click="showCardModal=false" style="flex:1;height:36px;background:#1e2130;border:.5px solid #2a2d3a;border-radius:6px;color:#94a3b8;font-size:12px;cursor:pointer">Cancel</button>
                <button @click="confirmCard()" :disabled="cardLast4.length !== 4"
                        style="flex:1;height:36px;background:#534AB7;border:none;border-radius:6px;color:#fff;font-size:12px;font-weight:500;cursor:pointer">Pay by card</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Unified POS screen component: products + cart in one Alpine scope
function posScreen() {
    return {
        // ── Product browsing ─────────────────────────────
        products: [],
        category: '',
        query: '',
        numpadTarget: 'cash',   // which field the on-screen numpad types into

        init() {
            this.loadProducts('');
        },

        async loadProducts(q) {
            try {
                const res = await fetch(`/api/products/search?q=${encodeURIComponent(q || '')}&category=${this.category}`);
                this.products = await res.json();
            } catch (e) {
                this.products = [];
            }
        },

        // called by the search/scan input (debounced)
        searchProducts(q) { this.loadProducts(q); },

        filterCategory(id, el) {
            this.category = id ?? '';
            document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
            el.classList.add('active');
            this.loadProducts(document.getElementById('scan-input').value);
        },

        // Enter on the scan bar: look up a barcode/name and add the first match
        handleScan(value) {
            if (!value) return;
            fetch(`/api/products/search?q=${encodeURIComponent(value)}&category=`)
                .then(r => r.json())
                .then(list => {
                    if (list.length) this.addToCart(list[0]);
                    else alert('No product found for: ' + value);
                });
        },

        cart: [],
        customer: null,
        cashStr: '0',
        couponCode: '',
        discountInput: '',
        discountMode: 'amount',   // 'amount' (Rs) or 'percent' (%)
        taxPercent: '',
        // popups
        showSaleModal: false,
        lastSale: null,
        showCardModal: false,
        cardLast4: '',

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

        addToCart(product) {
            const existing = this.cart.find(i => i.id === product.id);
            if (existing) {
                existing.qty++;
            } else {
                this.cart.push({ ...product, qty: 1 });
            }
        },

        changeQty(idx, d) {
            this.cart[idx].qty += d;
            if (this.cart[idx].qty <= 0) this.cart.splice(idx, 1);
        },

        removeItem(idx) { this.cart.splice(idx, 1); },
        clearCart() { this.cart = []; this.discountInput = ''; this.discountMode = 'amount'; this.taxPercent = ''; this.couponCode = ''; this.cashStr = '0'; },

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
        newCustomer: { name: '', phone: '', email: '' },
        customerError: '',

        openCustomerSearch() {
            this.customerError = '';
            this.customerQuery = '';
            this.customerResults = [];
            this.newCustomer = { name: '', phone: '', email: '' };
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
            if (method === 'cash') {
                if (this.cashNum < this.total) { alert('Insufficient cash amount!'); return; }
                this.processPayment('cash');
            } else if (method === 'card') {
                this.cardLast4 = '';
                this.showCardModal = true;   // capture last 4 digits first
            }
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
                paid_amount: method === 'cash' ? this.cashNum : this.total,
                card_last4: method === 'card' ? this.cardLast4 : null,
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
                    this.lastSale = data;        // { sale_id, invoice_no, total, change }
                    this.showSaleModal = true;
                    this.clearCart();
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
    };
}
</script>
@endpush
@endsection
