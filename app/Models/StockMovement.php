<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'movement_number',
        'medicine_id',
        'medicine_batch_id',
        'movement_type',
        'reason',
        'quantity',
        'unit',
        'stock_before',
        'stock_after',
        'reference_type',
        'reference_id',
        'created_by',
        'notes',
        'movement_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
        'movement_date' => 'datetime',
    ];

    protected $appends = ['movement_type_label', 'reason_label'];

    // Movement type constants
    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';

    // Reason constants
    const REASON_PURCHASE = 'purchase';
    const REASON_SALES = 'sales';
    const REASON_ADJUSTMENT_PLUS = 'adjustment_plus';
    const REASON_ADJUSTMENT_MINUS = 'adjustment_minus';
    const REASON_RETURN_SUPPLIER = 'return_supplier';
    const REASON_RETURN_PATIENT = 'return_patient';
    const REASON_EXPIRED = 'expired';
    const REASON_DAMAGE = 'damage';
    const REASON_TRANSFER_IN = 'transfer_in';
    const REASON_TRANSFER_OUT = 'transfer_out';
    const REASON_INITIAL_STOCK = 'initial_stock';
    const REASON_OTHER = 'other';

    /**
     * Generate movement number
     */
    public static function generateMovementNumber(): string
    {
        $prefix = 'SM';
        $date = Carbon::now()->format('Ymd');

        $lastMovement = self::whereDate('created_at', Carbon::today())
            ->orderBy('id', 'desc')
            ->first();

        if ($lastMovement) {
            $lastNumber = (int) substr($lastMovement->movement_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . $date . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get movement type label
     */
    public function getMovementTypeLabelAttribute(): string
    {
        return match($this->movement_type) {
            self::TYPE_IN => 'Masuk',
            self::TYPE_OUT => 'Keluar',
            default => $this->movement_type ?? 'Unknown',
        };
    }

    /**
     * Get reason label
     */
    public function getReasonLabelAttribute(): string
    {
        return match($this->reason) {
            self::REASON_PURCHASE => 'Pembelian',
            self::REASON_SALES => 'Penjualan/Penyerahan',
            self::REASON_ADJUSTMENT_PLUS => 'Penyesuaian (+)',
            self::REASON_ADJUSTMENT_MINUS => 'Penyesuaian (-)',
            self::REASON_RETURN_SUPPLIER => 'Retur ke Supplier',
            self::REASON_RETURN_PATIENT => 'Retur dari Pasien',
            self::REASON_EXPIRED => 'Kadaluarsa',
            self::REASON_DAMAGE => 'Rusak/Pecah',
            self::REASON_TRANSFER_IN => 'Mutasi Masuk',
            self::REASON_TRANSFER_OUT => 'Mutasi Keluar',
            self::REASON_INITIAL_STOCK => 'Stok Awal',
            self::REASON_OTHER => 'Lainnya',
            default => $this->reason ?? 'Unknown',
        };
    }

    /**
     * Create stock in movement
     */
    public static function createStockIn(
        int $medicineId,
        ?int $batchId,
        string $reason,
        int $quantity,
        string $unit,
        int $createdBy,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): self {
        $medicine = Medicine::findOrFail($medicineId);
        $stockBefore = $medicine->current_stock;

        return self::create([
            'movement_number' => self::generateMovementNumber(),
            'medicine_id' => $medicineId,
            'medicine_batch_id' => $batchId,
            'movement_type' => self::TYPE_IN,
            'reason' => $reason,
            'quantity' => $quantity,
            'unit' => $unit,
            'stock_before' => $stockBefore,
            'stock_after' => $stockBefore + $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => $createdBy,
            'notes' => $notes,
            'movement_date' => now(),
        ]);
    }

    /**
     * Create stock out movement
     */
    public static function createStockOut(
        int $medicineId,
        ?int $batchId,
        string $reason,
        int $quantity,
        string $unit,
        int $createdBy,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): self {
        $medicine = Medicine::findOrFail($medicineId);
        $stockBefore = $medicine->current_stock;

        return self::create([
            'movement_number' => self::generateMovementNumber(),
            'medicine_id' => $medicineId,
            'medicine_batch_id' => $batchId,
            'movement_type' => self::TYPE_OUT,
            'reason' => $reason,
            'quantity' => $quantity,
            'unit' => $unit,
            'stock_before' => $stockBefore,
            'stock_after' => $stockBefore - $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => $createdBy,
            'notes' => $notes,
            'movement_date' => now(),
        ]);
    }

    // Relationships
    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function medicineBatch(): BelongsTo
    {
        return $this->belongsTo(MedicineBatch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeStockIn($query)
    {
        return $query->where('movement_type', self::TYPE_IN);
    }

    public function scopeStockOut($query)
    {
        return $query->where('movement_type', self::TYPE_OUT);
    }

    public function scopeByReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    public function scopeByMedicine($query, int $medicineId)
    {
        return $query->where('medicine_id', $medicineId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('movement_date', [$startDate, $endDate]);
    }
}
