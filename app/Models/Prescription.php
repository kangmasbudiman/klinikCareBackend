<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        "medical_record_id",
        "prescription_number",
        "notes",
        "status",
    ];

    public const STATUS_LABELS = [
        "pending" => "Menunggu",
        "processed" => "Diproses",
        "completed" => "Selesai",
        "cancelled" => "Dibatalkan",
    ];

    protected $appends = ["status_label"];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public static function generatePrescriptionNumber(): string
    {
        $date = date("Ymd");
        $lastPrescription = self::whereDate("created_at", today())
            ->orderBy("id", "desc")
            ->first();

        if ($lastPrescription) {
            $lastNumber = (int) substr(
                $lastPrescription->prescription_number,
                -4,
            );
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf("RX-%s-%04d", $date, $newNumber);
    }
}
