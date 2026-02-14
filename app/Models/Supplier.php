<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'npwp',
        'payment_terms',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'payment_terms' => 'integer',
    ];

    /**
     * Generate supplier code
     */
    public static function generateCode(): string
    {
        $lastSupplier = self::orderBy('id', 'desc')->first();

        if ($lastSupplier) {
            $lastNumber = (int) substr($lastSupplier->code, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('SUP-%03d', $newNumber);
    }

    /**
     * Scope active suppliers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get purchase orders for this supplier
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Get purchase receipts for this supplier
     */
    public function purchaseReceipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class);
    }
}
