<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IcdCode extends Model
{
    use HasFactory;

    /**
     * ICD Types
     */
    const TYPE_ICD10 = 'icd10';
    const TYPE_ICD9CM = 'icd9cm';

    const TYPES = [
        self::TYPE_ICD10 => 'ICD-10 (Diagnosis)',
        self::TYPE_ICD9CM => 'ICD-9-CM (Prosedur)',
    ];

    /**
     * ICD-10 Chapters
     */
    const ICD10_CHAPTERS = [
        'I' => 'Certain infectious and parasitic diseases (A00-B99)',
        'II' => 'Neoplasms (C00-D48)',
        'III' => 'Diseases of the blood and blood-forming organs (D50-D89)',
        'IV' => 'Endocrine, nutritional and metabolic diseases (E00-E90)',
        'V' => 'Mental and behavioural disorders (F00-F99)',
        'VI' => 'Diseases of the nervous system (G00-G99)',
        'VII' => 'Diseases of the eye and adnexa (H00-H59)',
        'VIII' => 'Diseases of the ear and mastoid process (H60-H95)',
        'IX' => 'Diseases of the circulatory system (I00-I99)',
        'X' => 'Diseases of the respiratory system (J00-J99)',
        'XI' => 'Diseases of the digestive system (K00-K93)',
        'XII' => 'Diseases of the skin and subcutaneous tissue (L00-L99)',
        'XIII' => 'Diseases of the musculoskeletal system (M00-M99)',
        'XIV' => 'Diseases of the genitourinary system (N00-N99)',
        'XV' => 'Pregnancy, childbirth and the puerperium (O00-O99)',
        'XVI' => 'Certain conditions originating in the perinatal period (P00-P96)',
        'XVII' => 'Congenital malformations, deformations (Q00-Q99)',
        'XVIII' => 'Symptoms, signs and abnormal clinical findings (R00-R99)',
        'XIX' => 'Injury, poisoning and certain other consequences (S00-T98)',
        'XX' => 'External causes of morbidity and mortality (V01-Y98)',
        'XXI' => 'Factors influencing health status (Z00-Z99)',
        'XXII' => 'Codes for special purposes (U00-U99)',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'type',
        'name_id',
        'name_en',
        'chapter',
        'chapter_name',
        'block',
        'block_name',
        'parent_code',
        'dtd_code',
        'is_bpjs_claimable',
        'is_active',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_bpjs_claimable' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent ICD code.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(IcdCode::class, 'parent_code', 'code')
            ->where('type', $this->type);
    }

    /**
     * Get child ICD codes.
     */
    public function children(): HasMany
    {
        return $this->hasMany(IcdCode::class, 'parent_code', 'code')
            ->where('type', $this->type);
    }

    /**
     * Scope for active codes only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ICD-10 codes only.
     */
    public function scopeIcd10($query)
    {
        return $query->where('type', self::TYPE_ICD10);
    }

    /**
     * Scope for ICD-9-CM codes only.
     */
    public function scopeIcd9cm($query)
    {
        return $query->where('type', self::TYPE_ICD9CM);
    }

    /**
     * Scope for BPJS claimable codes.
     */
    public function scopeBpjsClaimable($query)
    {
        return $query->where('is_bpjs_claimable', true);
    }

    /**
     * Scope for searching by code or name.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('name_id', 'like', "%{$search}%")
              ->orWhere('name_en', 'like', "%{$search}%");
        });
    }

    /**
     * Get type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Get display name (prefer Indonesian).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name_id ?: $this->name_en ?: '-';
    }

    /**
     * Get full display (code + name).
     */
    public function getFullDisplayAttribute(): string
    {
        return "{$this->code} - {$this->display_name}";
    }

    /**
     * Check if this is a parent code (has children).
     */
    public function getIsParentAttribute(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if this is a leaf code (no children).
     */
    public function getIsLeafAttribute(): bool
    {
        return !$this->is_parent;
    }

    /**
     * Format for SatuSehat FHIR Condition resource.
     */
    public function toSatuSehatFormat(): array
    {
        return [
            'coding' => [
                [
                    'system' => $this->type === self::TYPE_ICD10
                        ? 'http://hl7.org/fhir/sid/icd-10'
                        : 'http://hl7.org/fhir/sid/icd-9-cm',
                    'code' => $this->code,
                    'display' => $this->name_en ?: $this->name_id,
                ],
            ],
            'text' => $this->name_id,
        ];
    }
}
