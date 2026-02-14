<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        "record_number",
        "queue_id",
        "patient_id",
        "department_id",
        "doctor_id",
        "visit_date",
        "chief_complaint",
        "present_illness",
        "past_medical_history",
        "family_history",
        "allergy_notes",
        "blood_pressure_systolic",
        "blood_pressure_diastolic",
        "heart_rate",
        "respiratory_rate",
        "temperature",
        "weight",
        "height",
        "oxygen_saturation",
        "physical_examination",
        "diagnosis",
        "diagnosis_notes",
        "treatment",
        "treatment_notes",
        "recommendations",
        "follow_up_date",
        "soap_subjective",
        "soap_objective",
        "soap_assessment",
        "soap_plan",
        "status",
        "completed_at",
    ];

    protected $casts = [
        "visit_date" => "date",
        "follow_up_date" => "date",
        "completed_at" => "datetime",
        "temperature" => "decimal:1",
        "weight" => "decimal:2",
        "height" => "decimal:2",
    ];

    protected $appends = ["blood_pressure", "bmi", "status_label"];

    public const STATUS_LABELS = [
        "in_progress" => "Sedang Diperiksa",
        "completed" => "Selesai",
        "cancelled" => "Dibatalkan",
    ];

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, "doctor_id");
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(MedicalRecordDiagnosis::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(MedicalRecordService::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function getBloodPressureAttribute(): ?string
    {
        if ($this->blood_pressure_systolic && $this->blood_pressure_diastolic) {
            return "{$this->blood_pressure_systolic}/{$this->blood_pressure_diastolic}";
        }
        return null;
    }

    public function getBmiAttribute(): ?float
    {
        if ($this->weight && $this->height) {
            $heightInMeters = $this->height / 100;
            return round(
                $this->weight / ($heightInMeters * $heightInMeters),
                2,
            );
        }
        return null;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public static function generateRecordNumber(): string
    {
        $date = date("Ymd");
        $lastRecord = self::whereDate("created_at", today())
            ->orderBy("id", "desc")
            ->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->record_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf("MR-%s-%04d", $date, $newNumber);
    }

    public function scopePending($query)
    {
        return $query->where("status", "in_progress");
    }

    public function scopeCompleted($query)
    {
        return $query->where("status", "completed");
    }

    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where("department_id", $departmentId);
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where("doctor_id", $doctorId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate("visit_date", today());
    }
}
