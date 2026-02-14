<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'prefix',
        'daily_quota',
        'start_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Check if quota is exceeded for today
     */
    public function isQuotaExceeded(): bool
    {
        $todayCount = Queue::where('department_id', $this->department_id)
            ->today()
            ->whereNotIn('status', ['cancelled'])
            ->count();

        return $todayCount >= $this->daily_quota;
    }

    /**
     * Get remaining quota for today
     */
    public function getRemainingQuota(): int
    {
        $todayCount = Queue::where('department_id', $this->department_id)
            ->today()
            ->whereNotIn('status', ['cancelled'])
            ->count();

        return max(0, $this->daily_quota - $todayCount);
    }

    /**
     * Scope for active settings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
