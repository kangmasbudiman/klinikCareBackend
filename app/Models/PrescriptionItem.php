<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        "prescription_id",
        "medicine_name",
        "dosage",
        "frequency",
        "duration",
        "quantity",
        "instructions",
        "notes",
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function getFullInstructionsAttribute(): string
    {
        $parts = [];

        if ($this->dosage) {
            $parts[] = $this->dosage;
        }
        if ($this->frequency) {
            $parts[] = $this->frequency;
        }
        if ($this->duration) {
            $parts[] = "selama {$this->duration}";
        }

        return implode(", ", $parts);
    }
}
