<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GoodsReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'purchase_order_id',
        'supplier_id',
        'receipt_date',
        'supplier_invoice_number',
        'supplier_invoice_date',
        'status',
        'total_amount',
        'received_by',
        'notes',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'supplier_invoice_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    protected $appends = ['status_label'];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Generate receipt number
     */
    public static function generateReceiptNumber(): string
    {
        $prefix = 'GR';
        $date = Carbon::now()->format('Ymd');

        $lastReceipt = self::whereDate('created_at', Carbon::today())
            ->orderBy('id', 'desc')
            ->first();

        if ($lastReceipt) {
            $lastNumber = (int) substr($lastReceipt->receipt_number, -4);
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
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => $this->status ?? 'Unknown',
        };
    }

    /**
     * Calculate total from items
     */
    public function calculateTotal(): void
    {
        $this->total_amount = $this->items->sum('total_price');
        $this->save();
    }

    /**
     * Complete the goods receipt - create batches and stock movements
     */
    public function complete(): bool
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        DB::beginTransaction();

        try {
            foreach ($this->items as $item) {
                // Create or update medicine batch
                $batch = MedicineBatch::create([
                    'medicine_id' => $item->medicine_id,
                    'batch_number' => $item->batch_number,
                    'expiry_date' => $item->expiry_date,
                    'initial_qty' => $item->quantity,
                    'current_qty' => $item->quantity,
                    'purchase_price' => $item->unit_price,
                    'status' => 'available',
                ]);

                // Update item with batch reference
                $item->medicine_batch_id = $batch->id;
                $item->save();

                // Create stock movement
                $medicine = $item->medicine;
                $stockBefore = $medicine->current_stock;

                StockMovement::create([
                    'movement_number' => StockMovement::generateMovementNumber(),
                    'medicine_id' => $item->medicine_id,
                    'medicine_batch_id' => $batch->id,
                    'movement_type' => 'in',
                    'reason' => 'purchase',
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBefore + $item->quantity,
                    'reference_type' => 'goods_receipt',
                    'reference_id' => $this->id,
                    'created_by' => $this->received_by,
                    'movement_date' => $this->receipt_date,
                ]);

                // Update PO item received quantity if linked
                if ($item->purchase_order_item_id) {
                    $item->purchaseOrderItem->addReceivedQuantity($item->quantity);
                }
            }

            $this->status = self::STATUS_COMPLETED;
            $this->save();

            // Update PO status if linked
            if ($this->purchase_order_id) {
                $this->purchaseOrder->updateReceivedStatus();
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // Relationships
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // Scopes
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
