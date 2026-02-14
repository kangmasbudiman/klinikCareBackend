<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory;

    /**
     * Service categories
     */
    const CATEGORIES = [
        'konsultasi' => 'Konsultasi',
        'tindakan' => 'Tindakan Medis',
        'laboratorium' => 'Laboratorium',
        'radiologi' => 'Radiologi',
        'farmasi' => 'Farmasi',
        'rawat_inap' => 'Rawat Inap',
        'lainnya' => 'Lainnya',
    ];

    /**
     * Available icons for services
     */
    const ICONS = [
        'stethoscope' => 'Stethoscope',
        'syringe' => 'Syringe',
        'pill' => 'Pill',
        'microscope' => 'Microscope',
        'scan' => 'Scan',
        'heart-pulse' => 'Heart Pulse',
        'thermometer' => 'Thermometer',
        'clipboard' => 'Clipboard',
        'activity' => 'Activity',
        'bed' => 'Bed',
        'scissors' => 'Scissors',
        'droplet' => 'Droplet',
        'eye' => 'Eye',
        'ear' => 'Ear',
        'bone' => 'Bone',
    ];

    /**
     * Available colors
     */
    const COLORS = [
        'blue' => 'Biru',
        'green' => 'Hijau',
        'red' => 'Merah',
        'yellow' => 'Kuning',
        'purple' => 'Ungu',
        'pink' => 'Pink',
        'indigo' => 'Indigo',
        'teal' => 'Teal',
        'orange' => 'Orange',
        'cyan' => 'Cyan',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'department_id',
        'base_price',
        'doctor_fee',
        'hospital_fee',
        'duration',
        'is_active',
        'requires_appointment',
        'icon',
        'color',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'doctor_fee' => 'decimal:2',
            'hospital_fee' => 'decimal:2',
            'duration' => 'integer',
            'is_active' => 'boolean',
            'requires_appointment' => 'boolean',
        ];
    }

    /**
     * Get the department that owns the service.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Scope a query to only include active services.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get total price (base + doctor + hospital fees).
     */
    public function getTotalPriceAttribute(): float
    {
        return (float) $this->base_price + (float) $this->doctor_fee + (float) $this->hospital_fee;
    }

    /**
     * Get category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Get color label.
     */
    public function getColorLabelAttribute(): string
    {
        return self::COLORS[$this->color] ?? $this->color;
    }

    /**
     * Get formatted base price.
     */
    public function getFormattedBasePriceAttribute(): string
    {
        return 'Rp ' . number_format($this->base_price, 0, ',', '.');
    }

    /**
     * Get formatted total price.
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }
}
