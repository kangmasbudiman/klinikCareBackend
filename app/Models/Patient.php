<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_record_number',
        'nik',
        'bpjs_number',
        'name',
        'birth_place',
        'birth_date',
        'gender',
        'blood_type',
        'religion',
        'marital_status',
        'occupation',
        'education',
        'phone',
        'email',
        'address',
        'rt',
        'rw',
        'village',
        'district',
        'city',
        'province',
        'postal_code',
        'emergency_contact_name',
        'emergency_contact_relation',
        'emergency_contact_phone',
        'allergies',
        'medical_notes',
        'patient_type',
        'insurance_name',
        'insurance_number',
        'photo',
        'satusehat_id',
        'is_active',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'age',
        'full_address',
        'gender_label',
        'patient_type_label',
        'religion_label',
        'marital_status_label',
    ];

    // Gender labels
    public const GENDER_LABELS = [
        'male' => 'Laki-laki',
        'female' => 'Perempuan',
    ];

    // Patient type labels
    public const PATIENT_TYPE_LABELS = [
        'umum' => 'Umum',
        'bpjs' => 'BPJS',
        'asuransi' => 'Asuransi',
    ];

    // Religion labels
    public const RELIGION_LABELS = [
        'islam' => 'Islam',
        'kristen' => 'Kristen',
        'katolik' => 'Katolik',
        'hindu' => 'Hindu',
        'buddha' => 'Buddha',
        'konghucu' => 'Konghucu',
        'lainnya' => 'Lainnya',
    ];

    // Marital status labels
    public const MARITAL_STATUS_LABELS = [
        'single' => 'Belum Menikah',
        'married' => 'Menikah',
        'divorced' => 'Cerai',
        'widowed' => 'Janda/Duda',
    ];

    // Blood type labels
    public const BLOOD_TYPE_LABELS = [
        'A' => 'A',
        'B' => 'B',
        'AB' => 'AB',
        'O' => 'O',
    ];

    /**
     * Get the queues for the patient
     */
    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    /**
     * Calculate age from birth date
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }
        return $this->birth_date->age;
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [];

        if ($this->address) {
            $parts[] = $this->address;
        }

        if ($this->rt && $this->rw) {
            $parts[] = "RT {$this->rt}/RW {$this->rw}";
        }

        if ($this->village) {
            $parts[] = "Kel. {$this->village}";
        }

        if ($this->district) {
            $parts[] = "Kec. {$this->district}";
        }

        if ($this->city) {
            $parts[] = $this->city;
        }

        if ($this->province) {
            $parts[] = $this->province;
        }

        if ($this->postal_code) {
            $parts[] = $this->postal_code;
        }

        return implode(', ', $parts);
    }

    /**
     * Get gender label
     */
    public function getGenderLabelAttribute(): string
    {
        return self::GENDER_LABELS[$this->gender] ?? $this->gender;
    }

    /**
     * Get patient type label
     */
    public function getPatientTypeLabelAttribute(): string
    {
        return self::PATIENT_TYPE_LABELS[$this->patient_type] ?? $this->patient_type;
    }

    /**
     * Get religion label
     */
    public function getReligionLabelAttribute(): ?string
    {
        if (!$this->religion) {
            return null;
        }
        return self::RELIGION_LABELS[$this->religion] ?? $this->religion;
    }

    /**
     * Get marital status label
     */
    public function getMaritalStatusLabelAttribute(): ?string
    {
        if (!$this->marital_status) {
            return null;
        }
        return self::MARITAL_STATUS_LABELS[$this->marital_status] ?? $this->marital_status;
    }

    /**
     * Generate medical record number
     */
    public static function generateMedicalRecordNumber(): string
    {
        $year = date('Y');
        $month = date('m');

        // Get last patient of current month
        $lastPatient = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPatient) {
            // Extract the sequence number from the last MRN
            $lastMrn = $lastPatient->medical_record_number;
            $lastSequence = (int) substr($lastMrn, -4);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        // Format: RM-YYYYMM-XXXX (e.g., RM-202601-0001)
        return sprintf('RM-%s%s-%04d', $year, $month, $newSequence);
    }

    /**
     * Scope for active patients
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for patient type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('patient_type', $type);
    }

    /**
     * Scope for search
     */
    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('medical_record_number', 'like', "%{$search}%")
                ->orWhere('nik', 'like', "%{$search}%")
                ->orWhere('bpjs_number', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }
}
