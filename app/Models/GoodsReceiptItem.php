<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'medicine_id',
        'medicine_batch_id',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
        'batch_number',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    protected $appends = ['is_expiring_soon', 'days_until_expiry'];

    /**
     * Check if expiring within 90 days
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->expiry_date) return false;
        return $this->expiry_date->diffInDays(now()) <= 90;
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) return null;
        return (int) now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Calculate total price
     */
    public function calculateTotal(): void
    {
        $this->total_price = $this->quantity * $this->unit_price;
        $this->save();
    }

    // Relationships
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function medicineBatch(): BelongsTo
    {
        return $this->belongsTo(MedicineBatch::class);
    }
}
