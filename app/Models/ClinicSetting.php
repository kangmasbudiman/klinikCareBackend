<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ClinicSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        // Basic Information
        "name",
        "tagline",
        "description",
        "logo",
        "favicon",

        // Contact Information
        "address",
        "city",
        "province",
        "postal_code",
        "phone",
        "phone_2",
        "whatsapp",
        "email",
        "website",

        // Social Media
        "facebook",
        "instagram",
        "twitter",

        // Legal Information
        "license_number",
        "npwp",
        "owner_name",

        // Operational Hours
        "operational_hours",

        // Settings
        "timezone",
        "currency",
        "date_format",
        "time_format",

        // Queue Settings
        "default_queue_quota",
        "appointment_duration",
    ];

    protected $casts = [
        "operational_hours" => "array",
        "default_queue_quota" => "integer",
        "appointment_duration" => "integer",
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ["logo_url", "favicon_url"];

    /**
     * Get full URL for logo
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }
        return Storage::disk("public")->url($this->logo);
    }

    /**
     * Get full URL for favicon
     */
    public function getFaviconUrlAttribute(): ?string
    {
        if (!$this->favicon) {
            return null;
        }
        return Storage::disk("public")->url($this->favicon);
    }

    /**
     * Default operational hours structure
     */
    public static function defaultOperationalHours(): array
    {
        return [
            "monday" => [
                "open" => "08:00",
                "close" => "21:00",
                "is_open" => true,
            ],
            "tuesday" => [
                "open" => "08:00",
                "close" => "21:00",
                "is_open" => true,
            ],
            "wednesday" => [
                "open" => "08:00",
                "close" => "21:00",
                "is_open" => true,
            ],
            "thursday" => [
                "open" => "08:00",
                "close" => "21:00",
                "is_open" => true,
            ],
            "friday" => [
                "open" => "08:00",
                "close" => "21:00",
                "is_open" => true,
            ],
            "saturday" => [
                "open" => "08:00",
                "close" => "17:00",
                "is_open" => true,
            ],
            "sunday" => [
                "open" => "08:00",
                "close" => "12:00",
                "is_open" => false,
            ],
        ];
    }

    /**
     * Get the singleton clinic setting
     */
    public static function getInstance(): self
    {
        $setting = self::first();

        if (!$setting) {
            $setting = self::create([
                "name" => "Klinik Anda",
                "operational_hours" => self::defaultOperationalHours(),
            ]);
        }

        return $setting;
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->province,
            $this->postal_code,
        ]);

        return implode(", ", $parts);
    }

    /**
     * Check if clinic is open on a specific day
     */
    public function isOpenOn(string $day): bool
    {
        $day = strtolower($day);
        $hours = $this->operational_hours ?? [];

        return isset($hours[$day]) && ($hours[$day]["is_open"] ?? false);
    }

    /**
     * Get opening hours for a specific day
     */
    public function getHoursFor(string $day): ?array
    {
        $day = strtolower($day);
        $hours = $this->operational_hours ?? [];

        return $hours[$day] ?? null;
    }
}
