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
.cart-panel{background:#161821;border-left:0.5px solid #2a2d3a;display:flex;flex-direction:column}
.cart-list{flex:1;overflow-y:auto;padding:8px 12px}
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
<div class="pos-wrap">
    {{-- LEFT: Products --}}
    <div style="display:flex;flex-direction:column;overflow:hidden">
        {{-- Scan bar --}}
        <div class="scan-bar">
            <i class="ti ti-scan" style="font-size:16px;color:#64748b"></i>
            <input class="scan-input" id="scan-input" placeholder="Scan barcode or search product..."
                   @keyup.enter="handleScan($event.target.value); $event.target.value=''"
                   x-on:input.debounce.300ms="searchProducts($event.target.value)">
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
        <div class="cats" x-data>
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
    <div class="cart-panel" x-data="posCart()">

        {{-- Cart header --}}
        <div style="padding:10px 12px;border-bottom:0.5px solid #2a2d3a">
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
                <span style="font-size:11px;color:#64748b;flex:1" x-text="customer ? customer.name : 'Walk-in customer'"></span>
                <span style="font-size:11px;color:#818cf8;cursor:pointer" @click="openCustomerSearch()">
                    <i class="ti ti-search" style="font-size:11px"></i>
                </span>
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
        <div style="padding:10px 12px;border-top:0.5px solid #2a2d3a">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:#94a3b8;margin-bottom:3px">
                <span>Subtotal</span><span x-text="'Rs. ' + subtotal.toLocaleString()"></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:12px;color:#4ade80;margin-bottom:3px" x-show="discount > 0">
                <span>Discount <span x-text="couponCode ? '(' + couponCode + ')' : ''"></span></span>
                <span x-text="'- Rs. ' + discount.toLocaleString()"></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:12px;color:#94a3b8;margin-bottom:3px" x-show="tax > 0">
                <span>Tax</span><span x-text="'Rs. ' + tax.toLocaleString()"></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:14px;color:#e2e8f0;font-weight:500;padding-top:6px;border-top:0.5px solid #2a2d3a;margin-top:4px">
                <span>Total</span><span x-text="'Rs. ' + total.toLocaleString()"></span>
            </div>
        </div>

        {{-- Numpad --}}
        <div style="padding:8px 12px;border-top:0.5px solid #2a2d3a">
            <div style="background:#0f1117;border:0.5px solid #2a2d3a;border-radius:6px;padding:6px 10px;margin-bottom:8px">
                <div style="font-size:10px;color:#64748b">Cash received</div>
                <div style="font-size:20px;font-weight:500;color:#e2e8f0;text-align:right" x-text="'Rs. ' + cashDisplay"></div>
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
</div>

@push('scripts')
<script>
// Product search store
document.addEventListener('alpine:init', () => {
    Alpine.store('pos', {
        products: @json($products ?? []),
        category: null,
        query: '',
    });
});

async function searchProducts(q) {
    const res = await fetch(`/api/products/search?q=${encodeURIComponent(q)}&category=${window._posCategory ?? ''}`);
    const data = await res.json();
    // update product grid via Alpine
    document.querySelectorAll('#product-grid [x-for]');
}

function filterCategory(id, el) {
    window._posCategory = id;
    document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
    el.classList.add('active');
    searchProducts(document.getElementById('scan-input').value);
}

// Cart Alpine component
function posCart() {
    return {
        cart: [],
        customer: null,
        cashStr: '0',
        couponCode: '',
        discount: 0,

        get cartCount() { return this.cart.reduce((a, i) => a + i.qty, 0); },
        get subtotal() { return this.cart.reduce((a, i) => a + i.price * i.qty, 0); },
        get tax() {
            return this.cart.reduce((a, i) => a + (i.price * i.qty * (i.tax_percent ?? 0) / 100), 0);
        },
        get total() { return Math.round((this.subtotal - this.discount + this.tax) * 100) / 100; },
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
        clearCart() { this.cart = []; this.discount = 0; this.couponCode = ''; this.cashStr = '0'; },

        np(v) {
            if (this.cashStr === '0' && v !== '.') this.cashStr = v;
            else this.cashStr += v;
        },
        npDel() { this.cashStr = this.cashStr.slice(0, -1) || '0'; },
        npExact() { this.cashStr = this.total.toFixed(2); },

        openCustomerSearch() {
            const name = prompt('Enter customer name or phone:');
            if (name) this.customer = { name };
        },

        openCouponModal() {
            const code = prompt('Enter coupon code:');
            if (!code) return;
            fetch(`/api/coupons/validate?code=${code}&amount=${this.subtotal}`)
                .then(r => r.json())
                .then(data => {
                    if (data.valid) {
                        this.couponCode = data.code;
                        this.discount = data.discount;
                        alert(`Coupon applied! Discount: Rs. ${data.discount.toLocaleString()}`);
                    } else {
                        alert('Invalid or expired coupon.');
                    }
                });
        },

        async pay(method) {
            if (this.cart.length === 0) { alert('Cart is empty!'); return; }
            if (method === 'cash' && this.cashNum < this.total) {
                alert('Insufficient cash amount!'); return;
            }

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
                payment_method: method,
                paid_amount: method === 'cash' ? this.cashNum : this.total,
                _token: document.querySelector('meta[name=csrf-token]').content,
            };

            const res = await fetch('/pos/sale', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });

            const data = await res.json();

            if (data.success) {
                const change = data.change > 0 ? `\nChange: Rs. ${data.change.toLocaleString()}` : '';
                alert(`✅ Payment successful!\nInvoice: ${data.invoice_no}\nTotal: Rs. ${data.total.toLocaleString()}${change}`);
                window.open(`/pos/receipt/${data.sale_id}`, '_blank', 'width=380,height=600');
                this.clearCart();
            } else {
                alert('Error: ' + data.message);
            }
        },
    };
}
</script>
@endpush
@endsection
