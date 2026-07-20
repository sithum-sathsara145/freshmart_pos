<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Sale extends Model {
    protected $fillable = ["invoice_no","customer_id","branch_id","counter_id","user_id","coupon_id","subtotal","discount_amount","tax_amount","total","paid_amount","cash_amount","change_amount","credit_amount","payment_method","status","notes","is_online_order","loyalty_points_earned","coupon_code","coupon_discount","credit_doc_url","credit_doc_public_id","credit_doc_uploaded_at"];
    protected $casts = ["total"=>"decimal:2","paid_amount"=>"decimal:2","credit_doc_uploaded_at"=>"datetime"];
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
    public function counter(): BelongsTo { return $this->belongsTo(Counter::class); }
    public function coupon(): BelongsTo  { return $this->belongsTo(Coupon::class); }
    public function items(): HasMany     { return $this->hasMany(SaleItem::class); }
    public function returns(): HasMany   { return $this->hasMany(SaleReturn::class); }
    public function payments(): HasMany  { return $this->hasMany(Payment::class); }
    public function balanceDue(): float  { return max(0, $this->total - $this->paid_amount); }
}