<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicineBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'medicine_id',
        'batch_number',
        'expiry_date',
        'initial_qty',
        'current_qty',
        'purchase_price',
        'purchase_receipt_item_id',
        'status',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'initial_qty' => 'integer',
        'current_qty' => 'integer',
        'purchase_price' => 'decimal:2',
    ];

    protected $appends = ['is_expired', 'is_expiring_soon', 'days_until_expiry'];

    const STATUS_AVAILABLE = 'available';
    const STATUS_LOW = 'low';
    const STATUS_EXPIRED = 'expired';
    const STATUS_EMPTY = 'empty';

    /**
     * Get the medicine
     */
    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    /**
     * Get the purchase receipt item
     */
    public function purchaseReceiptItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceiptItem::class);
    }

    /**
     * Get stock movements for this batch
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    /**
     * Check if batch is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date->isPast();
    }

    /**
     * Check if batch is expiring soon (within 3 months)
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        if ($this->is_expired) {
            return false;
        }

        return $this->expiry_date->lte(now()->addMonths(3));
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute(): int
    {
        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get formatted expiry date
     */
    public function getFormattedExpiryDateAttribute(): string
    {
        return $this->expiry_date->format('d M Y');
    }

    /**
     * Scope available batches
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', '!=', self::STATUS_EXPIRED)
            ->where('status', '!=', self::STATUS_EMPTY)
            ->where('current_qty', '>', 0)
            ->where('expiry_date', '>', now());
    }

    /**
     * Scope expired batches
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<=', now());
    }

    /**
     * Scope expiring soon (within n months)
     */
    public function scopeExpiringSoon($query, $months = 3)
    {
        return $query->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addMonths($months))
            ->where('current_qty', '>', 0);
    }

    /**
     * Scope ordered by FEFO (First Expired First Out)
     */
    public function scopeFefo($query)
    {
        return $query->orderBy('expiry_date', 'asc');
    }

    /**
     * Update status based on current state
     */
    public function updateStatus(): void
    {
        if ($this->is_expired) {
            $this->status = self::STATUS_EXPIRED;
        } elseif ($this->current_qty <= 0) {
            $this->status = self::STATUS_EMPTY;
        } elseif ($this->current_qty <= ($this->medicine->min_stock / 2)) {
            $this->status = self::STATUS_LOW;
        } else {
            $this->status = self::STATUS_AVAILABLE;
        }

        $this->save();
    }

    /**
     * Reduce stock from this batch
     */
    public function reduceStock(int $qty): bool
    {
        if ($qty > $this->current_qty) {
            return false;
        }

        $this->current_qty -= $qty;
        $this->updateStatus();

        return true;
    }

    /**
     * Add stock to this batch
     */
    public function addStock(int $qty): void
    {
        $this->current_qty += $qty;
        $this->updateStatus();
    }
}
