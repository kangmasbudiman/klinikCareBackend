<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        "code",
        "name",
        "generic_name",
        "category_id",
        "unit",
        "unit_conversion",
        "purchase_price",
        "margin_percentage",
        "ppn_percentage",
        "is_ppn_included",
        "price_before_ppn",
        "selling_price",
        "min_stock",
        "max_stock",
        "manufacturer",
        "description",
        "requires_prescription",
        "is_active",
    ];

    protected $casts = [
        "purchase_price" => "decimal:2",
        "margin_percentage" => "decimal:2",
        "ppn_percentage" => "decimal:2",
        "is_ppn_included" => "boolean",
        "price_before_ppn" => "decimal:2",
        "selling_price" => "decimal:2",
        "min_stock" => "integer",
        "max_stock" => "integer",
        "unit_conversion" => "integer",
        "requires_prescription" => "boolean",
        "is_active" => "boolean",
    ];

    protected $appends = ["current_stock", "stock_status"];

    /**
     * Generate medicine code
     */
    public static function generateCode(): string
    {
        $lastMedicine = self::orderBy("id", "desc")->first();

        if ($lastMedicine) {
            $lastNumber = (int) substr($lastMedicine->code, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf("MED-%04d", $newNumber);
    }

    /**
     * Scope active medicines
     */
    public function scopeActive($query)
    {
        return $query->where("is_active", true);
    }

    /**
     * Scope low stock medicines
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw(
            '(SELECT COALESCE(SUM(current_qty), 0) FROM medicine_batches WHERE medicine_id = medicines.id AND status != "expired" AND status != "empty") <= medicines.min_stock',
        );
    }

    /**
     * Get category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MedicineCategory::class, "category_id");
    }

    /**
     * Get all batches
     */
    public function batches(): HasMany
    {
        return $this->hasMany(MedicineBatch::class);
    }

    /**
     * Get available batches (not expired, has stock) ordered by expiry date (FEFO)
     */
    public function availableBatches(): HasMany
    {
        return $this->batches()
            ->where("status", "!=", "expired")
            ->where("status", "!=", "empty")
            ->where("current_qty", ">", 0)
            ->where("expiry_date", ">", now())
            ->orderBy("expiry_date", "asc");
    }

    /**
     * Get current total stock from all available batches
     */
    public function getCurrentStockAttribute(): int
    {
        return $this->batches()
            ->where("status", "!=", "expired")
            ->where("status", "!=", "empty")
            ->where("expiry_date", ">", now())
            ->sum("current_qty");
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute(): string
    {
        $currentStock = $this->current_stock;

        if ($currentStock <= 0) {
            return "out_of_stock";
        } elseif ($currentStock <= $this->min_stock) {
            return "low";
        } elseif ($currentStock >= $this->max_stock) {
            return "overstock";
        }

        return "normal";
    }

    /**
     * Get stock status label
     */
    public function getStockStatusLabelAttribute(): string
    {
        $labels = [
            "out_of_stock" => "Habis",
            "low" => "Stok Menipis",
            "normal" => "Normal",
            "overstock" => "Stok Berlebih",
        ];

        return $labels[$this->stock_status] ?? "Unknown";
    }

    /**
     * Get batches expiring soon (within 3 months)
     */
    public function getExpiringSoonBatchesAttribute()
    {
        $threeMonthsLater = now()->addMonths(3);

        return $this->batches()
            ->where("current_qty", ">", 0)
            ->where("expiry_date", ">", now())
            ->where("expiry_date", "<=", $threeMonthsLater)
            ->orderBy("expiry_date", "asc")
            ->get();
    }

    /**
     * Get formatted purchase price
     */
    public function getFormattedPurchasePriceAttribute(): string
    {
        return "Rp " . number_format($this->purchase_price, 0, ",", ".");
    }

    /**
     * Get formatted selling price
     */
    public function getFormattedSellingPriceAttribute(): string
    {
        return "Rp " . number_format($this->selling_price, 0, ",", ".");
    }

    /**
     * Get formatted price before PPN
     */
    public function getFormattedPriceBeforePpnAttribute(): string
    {
        return "Rp " . number_format($this->price_before_ppn, 0, ",", ".");
    }

    /**
     * Get PPN amount
     */
    public function getPpnAmountAttribute(): float
    {
        if ($this->is_ppn_included) {
            // Jika harga sudah termasuk PPN, hitung PPN dari harga jual
            return $this->selling_price -
                $this->selling_price / (1 + $this->ppn_percentage / 100);
        }
        return $this->price_before_ppn * ($this->ppn_percentage / 100);
    }

    /**
     * Get formatted PPN amount
     */
    public function getFormattedPpnAmountAttribute(): string
    {
        return "Rp " . number_format($this->ppn_amount, 0, ",", ".");
    }

    /**
     * Get margin amount
     */
    public function getMarginAmountAttribute(): float
    {
        return $this->purchase_price * ($this->margin_percentage / 100);
    }

    /**
     * Get formatted margin amount
     */
    public function getFormattedMarginAmountAttribute(): string
    {
        return "Rp " . number_format($this->margin_amount, 0, ",", ".");
    }

    /**
     * Calculate selling price from purchase price, margin, and PPN
     * Formula: selling_price = purchase_price * (1 + margin%) * (1 + ppn%)
     */
    public static function calculateSellingPrice(
        float $purchasePrice,
        float $marginPercentage,
        float $ppnPercentage,
    ): array {
        $priceBeforePpn = $purchasePrice * (1 + $marginPercentage / 100);
        $sellingPrice = $priceBeforePpn * (1 + $ppnPercentage / 100);

        return [
            "price_before_ppn" => round($priceBeforePpn, 2),
            "selling_price" => round($sellingPrice, 2),
            "margin_amount" => round(
                $purchasePrice * ($marginPercentage / 100),
                2,
            ),
            "ppn_amount" => round($priceBeforePpn * ($ppnPercentage / 100), 2),
        ];
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto calculate prices before saving
        static::saving(function ($medicine) {
            if ($medicine->purchase_price > 0) {
                $calculated = self::calculateSellingPrice(
                    $medicine->purchase_price,
                    $medicine->margin_percentage ?? 0,
                    $medicine->ppn_percentage ?? 11,
                );

                // Only auto-calculate if selling_price is not manually set or is zero
                if (
                    !$medicine->isDirty("selling_price") ||
                    $medicine->selling_price == 0
                ) {
                    $medicine->price_before_ppn =
                        $calculated["price_before_ppn"];
                    $medicine->selling_price = $calculated["selling_price"];
                }
            }
        });
    }
}
