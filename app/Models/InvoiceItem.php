<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        "invoice_id",
        "item_type",
        "item_name",
        "quantity",
        "unit_price",
        "total_price",
        "notes",
    ];

    protected $casts = [
        "unit_price" => "decimal:2",
        "total_price" => "decimal:2",
    ];

    public const ITEM_TYPE_LABELS = [
        "service" => "Layanan",
        "medicine" => "Obat",
        "other" => "Lainnya",
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getItemTypeLabelAttribute(): string
    {
        return self::ITEM_TYPE_LABELS[$this->item_type] ?? $this->item_type;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->total_price = $model->quantity * $model->unit_price;
        });
    }
}
