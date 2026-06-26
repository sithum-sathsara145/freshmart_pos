<?php

// ============================================================
// All Models for FreshMart POS
// Each model is in a separate namespace block
// Split these into individual files in app/Models/
// ============================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

// ======================== Sale ========================
class Sale extends Model
{
    protected $fillable = [
        'invoice_no','customer_id','branch_id','counter_id','user_id',
        'coupon_id','subtotal','discount_amount','tax_amount','total',
        'paid_amount','change_amount','payment_method','status','notes',
        'is_online_order',
    ];

    protected $casts = ['total' => 'decimal:2', 'paid_amount' => 'decimal:2'];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
    public function counter(): BelongsTo { return $this->belongsTo(Counter::class); }
    public function coupon(): BelongsTo  { return $this->belongsTo(Coupon::class); }
    public function items(): HasMany     { return $this->hasMany(SaleItem::class); }
    public function returns(): HasMany   { return $this->hasMany(SaleReturn::class); }
    public function payments(): HasMany  { return $this->hasMany(Payment::class); }

    public function balanceDue(): float  { return max(0, $this->total - $this->paid_amount); }
    public function scopePaid($q)        { return $q->where('status', 'paid'); }
    public function scopePartial($q)     { return $q->where('status', 'partial'); }
}

// ======================== SaleItem ========================
class SaleItem extends Model
{
    protected $fillable = ['sale_id','product_id','product_variation_id','quantity','unit_price','discount_percent','tax_percent','subtotal'];

    public function sale(): BelongsTo    { return $this->belongsTo(Sale::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}

// ======================== SaleReturn ========================
class SaleReturn extends Model
{
    protected $fillable = ['credit_note_no','sale_id','customer_id','reason','return_amount','refund_method','created_by'];

    public function sale(): BelongsTo     { return $this->belongsTo(Sale::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function items(): HasMany      { return $this->hasMany(SaleReturnItem::class); }
}

// ======================== SaleReturnItem ========================
class SaleReturnItem extends Model
{
    protected $fillable = ['sale_return_id','product_id','quantity','unit_price','subtotal'];

    public function saleReturn(): BelongsTo { return $this->belongsTo(SaleReturn::class); }
    public function product(): BelongsTo    { return $this->belongsTo(Product::class); }
}

// ======================== Purchase ========================
class Purchase extends Model
{
    protected $fillable = [
        'bill_no','supplier_id','branch_id','user_id',
        'subtotal','discount_amount','tax_amount','total',
        'paid_amount','balance_due','payment_status',
        'purchase_date','due_date','notes',
    ];

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function items(): HasMany      { return $this->hasMany(PurchaseItem::class); }
    public function returns(): HasMany    { return $this->hasMany(PurchaseReturn::class); }
    public function payments(): HasMany   { return $this->hasMany(Payment::class); }
}

// ======================== PurchaseItem ========================
class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id','product_id','quantity','unit_price','subtotal'];

    public function purchase(): BelongsTo { return $this->belongsTo(Purchase::class); }
    public function product(): BelongsTo  { return $this->belongsTo(Product::class); }
}

// ======================== PurchaseReturn ========================
class PurchaseReturn extends Model
{
    protected $fillable = ['dr_note_no','purchase_id','supplier_id','reason','return_amount','status','created_by'];

    public function purchase(): BelongsTo { return $this->belongsTo(Purchase::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
}

// ======================== Customer ========================
class Customer extends Model
{
    protected $fillable = ['name','phone','email','address','loyalty_points','loyalty_level','total_purchases'];

    public function sales(): HasMany { return $this->hasMany(Sale::class); }

    public function addLoyaltyPoints(float $amount): void
    {
        $points = (int) ($amount / 20);
        $this->increment('loyalty_points', $points);
        $this->updateLoyaltyLevel();
    }

    private function updateLoyaltyLevel(): void
    {
        $level = match(true) {
            $this->loyalty_points >= 5000  => 'platinum',
            $this->loyalty_points >= 3000  => 'gold',
            $this->loyalty_points >= 1000  => 'silver',
            default                        => 'bronze',
        };
        $this->update(['loyalty_level' => $level]);
    }
}

// ======================== Supplier ========================
class Supplier extends Model
{
    protected $fillable = ['name','contact_person','phone','email','address','city','total_purchases','balance_due'];

    public function purchases(): HasMany { return $this->hasMany(Purchase::class); }
    public function returns(): HasMany   { return $this->hasMany(PurchaseReturn::class); }
}

// ======================== Stock ========================
class Stock extends Model
{
    protected $fillable = ['product_id','branch_id','quantity'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }
}

// ======================== StockAdjustment ========================
class StockAdjustment extends Model
{
    protected $fillable = ['product_id','branch_id','type','quantity','reason','created_by'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }
}

// ======================== StockTransfer ========================
class StockTransfer extends Model
{
    protected $fillable = ['from_branch_id','to_branch_id','product_id','quantity','status','notes','created_by'];

    public function product(): BelongsTo    { return $this->belongsTo(Product::class); }
    public function fromBranch(): BelongsTo { return $this->belongsTo(Branch::class, 'from_branch_id'); }
    public function toBranch(): BelongsTo   { return $this->belongsTo(Branch::class, 'to_branch_id'); }
}

// ======================== Account ========================
class Account extends Model
{
    protected $fillable = ['name','type','branch_id','account_number','balance','status'];

    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function payments(): HasMany   { return $this->hasMany(Payment::class); }
}

// ======================== Payment ========================
class Payment extends Model
{
    protected $fillable = ['reference_no','type','account_id','to_account_id','party_type','party_id','sale_id','purchase_id','amount','method','notes','created_by'];

    public function account(): BelongsTo   { return $this->belongsTo(Account::class); }
    public function sale(): BelongsTo      { return $this->belongsTo(Sale::class); }
    public function purchase(): BelongsTo  { return $this->belongsTo(Purchase::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}

// ======================== Expense ========================
class Expense extends Model
{
    protected $fillable = ['expense_category_id','account_id','branch_id','description','amount','expense_date','receipt_image','created_by'];

    public function category(): BelongsTo { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function account(): BelongsTo  { return $this->belongsTo(Account::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
}

// ======================== ExpenseCategory ========================
class ExpenseCategory extends Model
{
    protected $fillable = ['name','description'];

    public function expenses(): HasMany { return $this->hasMany(Expense::class); }
}

// ======================== Branch ========================
class Branch extends Model
{
    protected $fillable = ['name','address','phone','city','is_main','status'];

    public function counters(): HasMany { return $this->hasMany(Counter::class); }
    public function stocks(): HasMany   { return $this->hasMany(Stock::class); }
    public function users(): HasMany    { return $this->hasMany(User::class); }
    public function staff(): HasMany    { return $this->hasMany(Staff::class); }
}

// ======================== Counter ========================
class Counter extends Model
{
    protected $fillable = ['branch_id','name','cash_balance','status'];

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function users(): HasMany    { return $this->hasMany(User::class); }
}

// ======================== Coupon ========================
class Coupon extends Model
{
    protected $fillable = ['code','type','value','min_order_amount','max_uses','used_count','expires_at','status'];

    public function isValid(float $orderAmount = 0): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at >= today())
            && ($this->max_uses === null || $this->used_count < $this->max_uses)
            && $orderAmount >= $this->min_order_amount;
    }

    public function calculateDiscount(float $amount): float
    {
        return $this->type === 'percentage'
            ? round($amount * $this->value / 100, 2)
            : min($this->value, $amount);
    }
}

// ======================== Quotation ========================
class Quotation extends Model
{
    protected $fillable = ['quote_no','customer_id','branch_id','user_id','subtotal','discount_amount','tax_amount','total','valid_till','notes','status'];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function items(): HasMany      { return $this->hasMany(QuotationItem::class); }
}

// ======================== QuotationItem ========================
class QuotationItem extends Model
{
    protected $fillable = ['quotation_id','product_id','quantity','unit_price','subtotal'];

    public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }
    public function product(): BelongsTo   { return $this->belongsTo(Product::class); }
}

// ======================== Setting ========================
class Setting extends Model
{
    protected $fillable = ['key_name','value'];

    public static function get(string $key, $default = null)
    {
        return static::where('key_name', $key)->value('value') ?? $default;
    }
}

// ======================== HRM Models ========================
class Staff extends Model
{
    protected $fillable = ['user_id','branch_id','name','phone','email','address','role','basic_salary','join_date','status'];

    public function branch(): BelongsTo       { return $this->belongsTo(Branch::class); }
    public function user(): BelongsTo         { return $this->belongsTo(User::class); }
    public function attendance(): HasMany     { return $this->hasMany(Attendance::class); }
    public function leaves(): HasMany         { return $this->hasMany(LeaveRequest::class); }
    public function payrolls(): HasMany       { return $this->hasMany(Payroll::class); }
    public function appreciations(): HasMany  { return $this->hasMany(Appreciation::class); }
}

class Attendance extends Model
{
    protected $fillable = ['staff_id','date','time_in','time_out','worked_hours','overtime_hours','status'];

    public function staff(): BelongsTo { return $this->belongsTo(Staff::class); }
}

class Holiday extends Model
{
    protected $fillable = ['name','date','type'];
}

class LeaveRequest extends Model
{
    protected $table    = 'leave_requests';
    protected $fillable = ['staff_id','type','from_date','to_date','days','reason','status','approved_by'];

    public function staff(): BelongsTo    { return $this->belongsTo(Staff::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}

class Payroll extends Model
{
    protected $fillable = ['staff_id','month','year','basic_salary','overtime_pay','allowances','deductions','epf_employee','epf_employer','etf','net_salary','status','paid_at'];

    public function staff(): BelongsTo { return $this->belongsTo(Staff::class); }
}

class Appreciation extends Model
{
    protected $fillable = ['staff_id','category','note','given_by'];

    public function staff(): BelongsTo   { return $this->belongsTo(Staff::class); }
    public function givenBy(): BelongsTo { return $this->belongsTo(User::class, 'given_by'); }
}

// ======================== Online Store ========================
class OnlineOrder extends Model
{
    protected $fillable = ['order_no','customer_id','customer_name','customer_phone','customer_address','delivery_type','subtotal','delivery_charge','total','status','notes','branch_id'];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function items(): HasMany      { return $this->hasMany(OnlineOrderItem::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
}

class OnlineOrderItem extends Model
{
    protected $fillable = ['online_order_id','product_id','quantity','unit_price','subtotal'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function order(): BelongsTo   { return $this->belongsTo(OnlineOrder::class, 'online_order_id'); }
}

class Banner extends Model
{
    protected $fillable = ['title','image','link','sort_order','status'];
}

// ======================== Category / Brand / Variation ========================
class Category extends Model
{
    protected $fillable = ['name','parent_id','description'];

    public function parent(): BelongsTo   { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children(): HasMany   { return $this->hasMany(Category::class, 'parent_id'); }
    public function products(): HasMany   { return $this->hasMany(Product::class); }
}

class Brand extends Model
{
    protected $fillable = ['name','description'];

    public function products(): HasMany { return $this->hasMany(Product::class); }
}

class VariationType extends Model
{
    protected $fillable = ['name'];

    public function values(): HasMany { return $this->hasMany(VariationValue::class); }
}

class VariationValue extends Model
{
    protected $fillable = ['variation_type_id','value'];

    public function type(): BelongsTo { return $this->belongsTo(VariationType::class, 'variation_type_id'); }
}

class ProductVariation extends Model
{
    protected $fillable = ['product_id','variation_value_id','barcode','purchase_price','sale_price'];

    public function product(): BelongsTo        { return $this->belongsTo(Product::class); }
    public function variationValue(): BelongsTo { return $this->belongsTo(VariationValue::class); }
}
