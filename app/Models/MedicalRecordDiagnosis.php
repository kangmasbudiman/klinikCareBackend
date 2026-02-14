<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecordDiagnosis extends Model
{
    use HasFactory;

    protected $fillable = [
        "medical_record_id",
        "icd_code",
        "icd_name",
        "diagnosis_type",
        "notes",
    ];

    public const DIAGNOSIS_TYPE_LABELS = [
        "primary" => "Primer",
        "secondary" => "Sekunder",
    ];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function getDiagnosisTypeLabelAttribute(): string
    {
        return self::DIAGNOSIS_TYPE_LABELS[$this->diagnosis_type] ??
            $this->diagnosis_type;
    }
}
