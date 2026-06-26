<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'name', 'barcode', 'sku', 'category_id', 'brand_id',
        'unit', 'purchase_price', 'sale_price', 'tax_percent',
        'discount_percent', 'min_stock', 'image', 'description',
        'show_in_online_store', 'status', 'created_by',
    ];

    protected $casts = [
        'purchase_price'  => 'decimal:2',
        'sale_price'      => 'decimal:2',
        'tax_percent'     => 'decimal:2',
        'discount_percent'=> 'decimal:2',
        'show_in_online_store' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockForBranch(int $branchId): float
    {
        return $this->stocks()
                    ->where('branch_id', $branchId)
                    ->value('quantity') ?? 0;
    }

    public function isLowStock(int $branchId): bool
    {
        return $this->stockForBranch($branchId) < $this->min_stock;
    }

    public function isOutOfStock(int $branchId): bool
    {
        return $this->stockForBranch($branchId) <= 0;
    }

    public function profitMargin(): float
    {
        if ($this->purchase_price <= 0) return 0;
        return round(($this->sale_price - $this->purchase_price) / $this->purchase_price * 100, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLowStock($query, int $branchId)
    {
        return $query->whereHas('stocks', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
              ->whereRaw('quantity < products.min_stock');
        });
    }

    public function scopeOnlineStore($query)
    {
        return $query->where('show_in_online_store', true);
    }
}
