<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends Model
{
    use HasFactory;

    /**
     * Day of week constants
     */
    public const DAY_SUNDAY = 0;
    public const DAY_MONDAY = 1;
    public const DAY_TUESDAY = 2;
    public const DAY_WEDNESDAY = 3;
    public const DAY_THURSDAY = 4;
    public const DAY_FRIDAY = 5;
    public const DAY_SATURDAY = 6;

    /**
     * Day labels in Indonesian
     */
    public const DAY_LABELS = [
        self::DAY_SUNDAY => 'Minggu',
        self::DAY_MONDAY => 'Senin',
        self::DAY_TUESDAY => 'Selasa',
        self::DAY_WEDNESDAY => 'Rabu',
        self::DAY_THURSDAY => 'Kamis',
        self::DAY_FRIDAY => 'Jumat',
        self::DAY_SATURDAY => 'Sabtu',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'doctor_id',
        'department_id',
        'day_of_week',
        'start_time',
        'end_time',
        'quota',
        'is_active',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'day_of_week' => 'integer',
        'quota' => 'integer',
        'is_active' => 'boolean',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'day_label',
        'time_range',
    ];

    /**
     * Get the doctor (user) that owns the schedule.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the department that owns the schedule.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get day label attribute.
     */
    public function getDayLabelAttribute(): string
    {
        return self::DAY_LABELS[$this->day_of_week] ?? '';
    }

    /**
     * Get time range attribute.
     */
    public function getTimeRangeAttribute(): string
    {
        $start = date('H:i', strtotime($this->start_time));
        $end = date('H:i', strtotime($this->end_time));
        return "{$start} - {$end}";
    }

    /**
     * Scope a query to only include active schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by day of week.
     */
    public function scopeForDay($query, int $day)
    {
        return $query->where('day_of_week', $day);
    }

    /**
     * Scope a query to filter by today.
     */
    public function scopeToday($query)
    {
        $today = now()->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
        return $query->where('day_of_week', $today);
    }

    /**
     * Scope a query to filter by department.
     */
    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope a query to filter by doctor.
     */
    public function scopeForDoctor($query, int $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Check if the schedule is currently available (within time range).
     */
    public function isCurrentlyAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        $currentDay = $now->dayOfWeek;

        if ($this->day_of_week !== $currentDay) {
            return false;
        }

        $currentTime = $now->format('H:i:s');
        $startTime = date('H:i:s', strtotime($this->start_time));
        $endTime = date('H:i:s', strtotime($this->end_time));

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    /**
     * Get all day options for dropdown.
     */
    public static function getDayOptions(): array
    {
        return collect(self::DAY_LABELS)->map(function ($label, $value) {
            return ['value' => $value, 'label' => $label];
        })->values()->toArray();
    }
}
