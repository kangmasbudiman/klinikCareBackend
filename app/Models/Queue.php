<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Queue extends Model
{
    use HasFactory;

    protected $fillable = [
        "queue_number",
        "queue_code",
        "queue_date",
        "patient_id",
        "department_id",
        "doctor_id",
        "service_id",
        "status",
        "called_at",
        "started_at",
        "completed_at",
        "counter_number",
        "served_by",
        "notes",
    ];

    protected $casts = [
        "queue_date" => "date",
        "called_at" => "datetime",
        "started_at" => "datetime",
        "completed_at" => "datetime",
    ];

    protected $appends = [
        "status_label",
        "status_color",
        "wait_time",
        "service_time",
    ];

    // Status labels
    public const STATUS_LABELS = [
        "waiting" => "Menunggu",
        "called" => "Dipanggil",
        "in_service" => "Dilayani",
        "completed" => "Selesai",
        "skipped" => "Dilewati",
        "cancelled" => "Dibatalkan",
    ];

    // Status colors for UI
    public const STATUS_COLORS = [
        "waiting" => "yellow",
        "called" => "blue",
        "in_service" => "purple",
        "completed" => "green",
        "skipped" => "orange",
        "cancelled" => "red",
    ];

    /**
     * Get the patient
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the doctor
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, "doctor_id");
    }

    /**
     * Get the service
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the user who served
     */
    public function servedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "served_by");
    }

    /**
     * Get the medical record for this queue
     */
    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? "gray";
    }

    /**
     * Get wait time in minutes
     */
    public function getWaitTimeAttribute(): ?int
    {
        if (!$this->called_at) {
            // If not called yet, calculate from creation
            return $this->created_at->diffInMinutes(now());
        }

        // Calculate from creation to called
        return $this->created_at->diffInMinutes($this->called_at);
    }

    /**
     * Get service time in minutes
     */
    public function getServiceTimeAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInMinutes($this->completed_at);
    }

    /**
     * Generate queue code
     */
    public static function generateQueueCode(
        string $prefix,
        int $number,
    ): string {
        return sprintf("%s-%03d", $prefix, $number);
    }

    /**
     * Get next queue number for department on a date
     */
    public static function getNextQueueNumber(
        int $departmentId,
        string $date,
    ): int {
        $lastQueue = self::where("department_id", $departmentId)
            ->where("queue_date", $date)
            ->orderBy("queue_number", "desc")
            ->first();

        if ($lastQueue) {
            return $lastQueue->queue_number + 1;
        }

        // Get start number from queue settings
        $setting = QueueSetting::where("department_id", $departmentId)->first();
        return $setting ? $setting->start_number : 1;
    }

    /**
     * Scope for today's queues
     */
    public function scopeToday($query)
    {
        return $query->where("queue_date", now()->toDateString());
    }

    /**
     * Scope for specific date
     */
    public function scopeOnDate($query, string $date)
    {
        return $query->where("queue_date", $date);
    }

    /**
     * Scope for department
     */
    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where("department_id", $departmentId);
    }

    /**
     * Scope for waiting queues
     */
    public function scopeWaiting($query)
    {
        return $query->where("status", "waiting");
    }

    /**
     * Scope for active queues (waiting or called or in_service)
     */
    public function scopeActive($query)
    {
        return $query->whereIn("status", ["waiting", "called", "in_service"]);
    }

    /**
     * Scope for completed queues
     */
    public function scopeCompleted($query)
    {
        return $query->where("status", "completed");
    }
}
