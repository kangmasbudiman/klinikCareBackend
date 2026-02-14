<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{
    use HasFactory;

    /**
     * Available colors for departments
     */
    const COLORS = [
        "blue" => "Biru",
        "green" => "Hijau",
        "red" => "Merah",
        "yellow" => "Kuning",
        "purple" => "Ungu",
        "pink" => "Pink",
        "indigo" => "Indigo",
        "teal" => "Teal",
        "orange" => "Orange",
        "cyan" => "Cyan",
    ];

    /**
     * Available icons for departments
     */
    const ICONS = [
        "stethoscope" => "Stethoscope",
        "heart" => "Heart",
        "baby" => "Baby",
        "eye" => "Eye",
        "ear" => "Ear",
        "bone" => "Bone",
        "brain" => "Brain",
        "lungs" => "Lungs",
        "pill" => "Pill",
        "syringe" => "Syringe",
        "microscope" => "Microscope",
        "activity" => "Activity",
        "thermometer" => "Thermometer",
        "clipboard" => "Clipboard",
        "user" => "User",
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        "code",
        "name",
        "description",
        "icon",
        "color",
        "quota_per_day",
        "default_service_id",
        "is_active",
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "is_active" => "boolean",
            "quota_per_day" => "integer",
        ];
    }

    /**
     * Scope a query to only include active departments.
     */
    public function scopeActive($query)
    {
        return $query->where("is_active", true);
    }

    /**
     * Get color label
     */
    public function getColorLabelAttribute(): string
    {
        return self::COLORS[$this->color] ?? $this->color;
    }

    /**
     * Get users (doctors/nurses) assigned to this department
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, "user_departments")
            ->withPivot("is_primary")
            ->withTimestamps();
    }

    /**
     * Get doctors assigned to this department
     */
    public function doctors(): BelongsToMany
    {
        return $this->users()->where("role", User::ROLE_DOKTER);
    }

    /**
     * Get nurses assigned to this department
     */
    public function nurses(): BelongsToMany
    {
        return $this->users()->where("role", User::ROLE_PERAWAT);
    }

    /**
     * Get the default service (konsultasi) for this department
     */
    public function defaultService(): BelongsTo
    {
        return $this->belongsTo(Service::class, "default_service_id");
    }
}
