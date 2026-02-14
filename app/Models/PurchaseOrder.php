<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'order_date',
        'expected_delivery_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'created_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected $appends = ['status_label', 'status_color'];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ORDERED = 'ordered';
    const STATUS_PARTIAL_RECEIVED = 'partial_received';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Generate PO number
     */
    public static function generatePoNumber(): string
    {
        $prefix = 'PO';
        $date = Carbon::now()->format('Ymd');

        $lastPo = self::whereDate('created_at', Carbon::today())
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPo) {
            $lastNumber = (int) substr($lastPo->po_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . $date . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_APPROVAL => 'Menunggu Persetujuan',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_ORDERED => 'Dipesan',
            self::STATUS_PARTIAL_RECEIVED => 'Diterima Sebagian',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => $this->status ?? 'Unknown',
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_PENDING_APPROVAL => 'yellow',
            self::STATUS_APPROVED => 'blue',
            self::STATUS_REJECTED => 'red',
            self::STATUS_ORDERED => 'indigo',
            self::STATUS_PARTIAL_RECEIVED => 'orange',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Calculate totals from items
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total_price');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->discount_amount = $this->items->sum('discount_amount');
        $this->total_amount = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->save();
    }

    /**
     * Submit for approval
     */
    public function submitForApproval(): bool
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        $this->status = self::STATUS_PENDING_APPROVAL;
        return $this->save();
    }

    /**
     * Approve PO
     */
    public function approve(int $userId, ?string $notes = null): bool
    {
        if ($this->status !== self::STATUS_PENDING_APPROVAL) {
            return false;
        }

        $this->status = self::STATUS_APPROVED;
        $this->approved_by = $userId;
        $this->approved_at = now();
        $this->approval_notes = $notes;
        return $this->save();
    }

    /**
     * Reject PO
     */
    public function reject(int $userId, string $reason): bool
    {
        if ($this->status !== self::STATUS_PENDING_APPROVAL) {
            return false;
        }

        $this->status = self::STATUS_REJECTED;
        $this->rejected_by = $userId;
        $this->rejected_at = now();
        $this->rejection_reason = $reason;
        return $this->save();
    }

    /**
     * Mark as ordered (sent to supplier)
     */
    public function markAsOrdered(): bool
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        $this->status = self::STATUS_ORDERED;
        return $this->save();
    }

    /**
     * Check and update received status
     */
    public function updateReceivedStatus(): void
    {
        $totalOrdered = $this->items->sum('quantity');
        $totalReceived = $this->items->sum('received_quantity');

        if ($totalReceived >= $totalOrdered) {
            $this->status = self::STATUS_COMPLETED;
        } elseif ($totalReceived > 0) {
            $this->status = self::STATUS_PARTIAL_RECEIVED;
        }

        $this->save();
    }

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // Scopes
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeNeedsReceiving($query)
    {
        return $query->whereIn('status', [
            self::STATUS_ORDERED,
            self::STATUS_PARTIAL_RECEIVED
        ]);
    }
}
