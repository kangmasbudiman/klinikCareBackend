<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalLetter extends Model
{
    use HasFactory;

    protected $fillable = [
        'letter_number',
        'letter_type',
        'patient_id',
        'doctor_id',
        'medical_record_id',
        'letter_date',
        'purpose',
        'notes',
        'sick_start_date',
        'sick_end_date',
        'sick_days',
        'referral_destination',
        'referral_specialist',
        'referral_reason',
        'diagnosis_summary',
        'treatment_summary',
        'health_purpose',
        'examination_result',
        'statement_content',
        'created_by',
    ];

    protected $casts = [
        'letter_date' => 'date',
        'sick_start_date' => 'date',
        'sick_end_date' => 'date',
    ];

    protected $appends = ['letter_type_label'];

    public const LETTER_TYPE_LABELS = [
        'surat_sehat' => 'Surat Keterangan Sehat',
        'surat_sakit' => 'Surat Keterangan Sakit',
        'surat_rujukan' => 'Surat Rujukan',
        'surat_keterangan' => 'Surat Keterangan Dokter',
    ];

    public const LETTER_TYPE_PREFIXES = [
        'surat_sehat' => 'SKS',
        'surat_sakit' => 'SKK',
        'surat_rujukan' => 'SRJ',
        'surat_keterangan' => 'SKD',
    ];

    // Relationships

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors

    public function getLetterTypeLabelAttribute(): string
    {
        return self::LETTER_TYPE_LABELS[$this->letter_type] ?? $this->letter_type;
    }

    // Helpers

    public static function generateLetterNumber(string $type): string
    {
        $prefix = self::LETTER_TYPE_PREFIXES[$type] ?? 'SRT';
        $year = now()->format('Y');
        $month = now()->format('m');

        $count = self::where('letter_type', $type)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        return sprintf('%s/%s/%s/%03d', $prefix, $year, $month, $count);
    }

    // Scopes

    public function scopeOfType($query, string $type)
    {
        return $query->where('letter_type', $type);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeForDoctor($query, int $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }
}
