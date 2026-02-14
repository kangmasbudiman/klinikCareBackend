<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecordService extends Model
{
    use HasFactory;

    protected $fillable = [
        "medical_record_id",
        "service_id",
        "service_name",
        "quantity",
        "unit_price",
        "total_price",
        "notes",
    ];

    protected $casts = [
        "unit_price" => "decimal:2",
        "total_price" => "decimal:2",
    ];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->total_price = $model->quantity * $model->unit_price;
        });
    }
}
