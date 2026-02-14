<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'medicine_id',
        'quantity',
        'unit',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_percent',
        'tax_amount',
        'total_price',
        'received_quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'received_quantity' => 'integer',
    ];

    protected $appends = ['remaining_quantity', 'is_fully_received'];

    /**
     * Get remaining quantity to receive
     */
    public function getRemainingQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->received_quantity);
    }

    /**
     * Check if fully received
     */
    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->received_quantity >= $this->quantity;
    }

    /**
     * Calculate item totals
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->quantity * $this->unit_price;

        // Calculate discount
        if ($this->discount_percent > 0) {
            $this->discount_amount = $subtotal * ($this->discount_percent / 100);
        }

        $afterDiscount = $subtotal - $this->discount_amount;

        // Calculate tax
        if ($this->tax_percent > 0) {
            $this->tax_amount = $afterDiscount * ($this->tax_percent / 100);
        }

        $this->total_price = $afterDiscount + $this->tax_amount;
        $this->save();
    }

    /**
     * Add received quantity
     */
    public function addReceivedQuantity(int $quantity): void
    {
        $this->received_quantity += $quantity;
        $this->save();

        // Update parent PO status
        $this->purchaseOrder->updateReceivedStatus();
    }

    // Relationships
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function goodsReceiptItems(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
}
