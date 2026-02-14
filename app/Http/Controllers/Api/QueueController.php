<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use App\Models\QueueSetting;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    /**
     * Display a listing of queues
     */
    public function index(Request $request): JsonResponse
    {
        $query = Queue::with(["patient", "department", "service", "servedBy"]);

        // Date filter (default today)
        $date = $request->get("date", now()->toDateString());
        $query->where("queue_date", $date);

        // Department filter
        if ($request->has("department_id") && $request->department_id) {
            $query->where("department_id", $request->department_id);
        }

        // Status filter
        if ($request->has("status") && $request->status) {
            $query->where("status", $request->status);
        }

        // Sort by queue number
        $query->orderBy("queue_number", "asc");

        // Paginate
        $perPage = $request->get("per_page", 20);
        $queues = $query->paginate($perPage);

        return response()->json([
            "success" => true,
            "data" => $queues->items(),
            "meta" => [
                "current_page" => $queues->currentPage(),
                "last_page" => $queues->lastPage(),
                "per_page" => $queues->perPage(),
                "total" => $queues->total(),
            ],
        ]);
    }

    /**
     * Get today's queues grouped by department
     */
    public function today(Request $request): JsonResponse
    {
        $departmentId = $request->get("department_id");

        $query = Queue::with(["patient", "department", "service", "servedBy"])
            ->today()
            ->orderBy("queue_number", "asc");

        if ($departmentId) {
            $query->where("department_id", $departmentId);
        }

        $queues = $query->get();

        // Group by status
        $grouped = [
            "waiting" => $queues->where("status", "waiting")->values(),
            "called" => $queues->where("status", "called")->values(),
            "in_service" => $queues->where("status", "in_service")->values(),
            "completed" => $queues->where("status", "completed")->values(),
            "skipped" => $queues->where("status", "skipped")->values(),
            "cancelled" => $queues->where("status", "cancelled")->values(),
        ];

        return response()->json([
            "success" => true,
            "data" => $grouped,
            "total" => $queues->count(),
        ]);
    }

    /**
     * Get queue statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $date = $request->get("date", now()->toDateString());
        $departmentId = $request->get("department_id");

        $query = Queue::where("queue_date", $date);

        if ($departmentId) {
            $query->where("department_id", $departmentId);
        }

        $total = (clone $query)->count();
        $waiting = (clone $query)->where("status", "waiting")->count();
        $called = (clone $query)->where("status", "called")->count();
        $inService = (clone $query)->where("status", "in_service")->count();
        $completed = (clone $query)->where("status", "completed")->count();
        $skipped = (clone $query)->where("status", "skipped")->count();
        $cancelled = (clone $query)->where("status", "cancelled")->count();

        // Average wait time (for completed queues)
        $avgWaitTime = (clone $query)
            ->where("status", "completed")
            ->whereNotNull("called_at")
            ->selectRaw(
                "AVG(TIMESTAMPDIFF(MINUTE, created_at, called_at)) as avg_wait",
            )
            ->value("avg_wait");

        // Average service time
        $avgServiceTime = (clone $query)
            ->where("status", "completed")
            ->whereNotNull("started_at")
            ->whereNotNull("completed_at")
            ->selectRaw(
                "AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_service",
            )
            ->value("avg_service");

        return response()->json([
            "success" => true,
            "data" => [
                "total" => $total,
                "waiting" => $waiting,
                "called" => $called,
                "in_service" => $inService,
                "completed" => $completed,
                "skipped" => $skipped,
                "cancelled" => $cancelled,
                "avg_wait_time" => round($avgWaitTime ?? 0),
                "avg_service_time" => round($avgServiceTime ?? 0),
            ],
        ]);
    }

    /**
     * Get data for queue display (TV/Monitor)
     */
    public function display(Request $request): JsonResponse
    {
        $departmentId = $request->get("department_id");

        $query = Queue::with([
            "patient:id,name,medical_record_number",
            "department:id,name,code,color",
        ])->today();

        if ($departmentId) {
            $query->where("department_id", $departmentId);
        }

        // Current being served
        $current = (clone $query)
            ->whereIn("status", ["called", "in_service"])
            ->orderBy("called_at", "desc")
            ->get()
            ->map(function ($queue) {
                return [
                    "queue_code" => $queue->queue_code,
                    "department" => $queue->department->name,
                    "department_color" => $queue->department->color,
                    "counter" => $queue->counter_number,
                    "status" => $queue->status,
                    "patient_name" => $queue->patient?->name,
                ];
            });

        // Waiting list (next 10)
        $waiting = (clone $query)
            ->where("status", "waiting")
            ->orderBy("queue_number", "asc")
            ->limit(10)
            ->get()
            ->map(function ($queue) {
                return [
                    "queue_code" => $queue->queue_code,
                    "department" => $queue->department->name,
                    "department_color" => $queue->department->color,
                ];
            });

        return response()->json([
            "success" => true,
            "data" => [
                "current" => $current,
                "waiting" => $waiting,
                "timestamp" => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get current queue for a department
     */
    public function current(Department $department): JsonResponse
    {
        $current = Queue::with(["patient", "service", "servedBy"])
            ->today()
            ->where("department_id", $department->id)
            ->whereIn("status", ["called", "in_service"])
            ->orderBy("called_at", "desc")
            ->first();

        $nextWaiting = Queue::today()
            ->where("department_id", $department->id)
            ->where("status", "waiting")
            ->orderBy("queue_number", "asc")
            ->first();

        $setting = QueueSetting::where(
            "department_id",
            $department->id,
        )->first();

        return response()->json([
            "success" => true,
            "data" => [
                "current" => $current,
                "next" => $nextWaiting,
                "remaining_quota" => $setting
                    ? $setting->getRemainingQuota()
                    : 0,
            ],
        ]);
    }

    /**
     * Take a new queue number
     */
    public function take(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "department_id" => ["required", "exists:departments,id"],
            "doctor_id" => ["nullable", "exists:users,id"],
            "patient_id" => ["nullable", "exists:patients,id"],
            "service_id" => ["nullable", "exists:services,id"],
        ]);

        // Check if queue setting exists for department
        $setting = QueueSetting::where(
            "department_id",
            $validated["department_id"],
        )->first();

        if (!$setting) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Pengaturan antrian belum dikonfigurasi untuk departemen ini",
                ],
                422,
            );
        }

        if (!$setting->is_active) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Antrian untuk departemen ini sedang tidak aktif",
                ],
                422,
            );
        }

        // Check quota
        if ($setting->isQuotaExceeded()) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Kuota antrian untuk hari ini sudah habis",
                ],
                422,
            );
        }

        // Get next queue number
        $today = now()->toDateString();
        $nextNumber = Queue::getNextQueueNumber(
            $validated["department_id"],
            $today,
        );
        $queueCode = Queue::generateQueueCode($setting->prefix, $nextNumber);

        $queue = Queue::create([
            "queue_number" => $nextNumber,
            "queue_code" => $queueCode,
            "queue_date" => $today,
            "patient_id" => $validated["patient_id"] ?? null,
            "department_id" => $validated["department_id"],
            "doctor_id" => $validated["doctor_id"] ?? null,
            "service_id" => $validated["service_id"] ?? null,
            "status" => "waiting",
        ]);

        $queue->load(["patient", "department", "doctor", "service"]);

        return response()->json(
            [
                "success" => true,
                "message" => "Nomor antrian berhasil diambil",
                "data" => $queue,
            ],
            201,
        );
    }

    /**
     * Call a queue
     */
    public function call(Request $request, Queue $queue): JsonResponse
    {
        if ($queue->status !== "waiting" && $queue->status !== "skipped") {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Antrian tidak dapat dipanggil (status: " .
                        $queue->status_label .
                        ")",
                ],
                422,
            );
        }

        $validated = $request->validate([
            "counter_number" => ["nullable", "integer", "min:1"],
        ]);

        $queue->update([
            "status" => "called",
            "called_at" => now(),
            "counter_number" => $validated["counter_number"] ?? null,
            "served_by" => auth()->id(),
        ]);

        $queue->load(["patient", "department", "service", "servedBy"]);

        return response()->json([
            "success" => true,
            "message" =>
                "Antrian " . $queue->queue_code . " berhasil dipanggil",
            "data" => $queue,
        ]);
    }

    /**
     * Start serving a queue
     */
    public function start(Queue $queue): JsonResponse
    {
        if ($queue->status !== "called") {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Antrian harus dipanggil terlebih dahulu",
                ],
                422,
            );
        }

        $queue->update([
            "status" => "in_service",
            "started_at" => now(),
        ]);

        $queue->load(["patient", "department", "service", "servedBy"]);

        return response()->json([
            "success" => true,
            "message" => "Pelayanan dimulai",
            "data" => $queue,
        ]);
    }

    /**
     * Complete serving a queue
     */
    public function complete(Request $request, Queue $queue): JsonResponse
    {
        if ($queue->status !== "in_service") {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Antrian belum dalam status dilayani",
                ],
                422,
            );
        }

        $validated = $request->validate([
            "notes" => ["nullable", "string"],
        ]);

        $queue->update([
            "status" => "completed",
            "completed_at" => now(),
            "notes" => $validated["notes"] ?? $queue->notes,
        ]);

        $queue->load(["patient", "department", "service", "servedBy"]);

        return response()->json([
            "success" => true,
            "message" => "Pelayanan selesai",
            "data" => $queue,
        ]);
    }

    /**
     * Skip a queue
     */
    public function skip(Request $request, Queue $queue): JsonResponse
    {
        if (!in_array($queue->status, ["waiting", "called"])) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Antrian tidak dapat dilewati",
                ],
                422,
            );
        }

        $validated = $request->validate([
            "notes" => ["nullable", "string"],
        ]);

        $queue->update([
            "status" => "skipped",
            "notes" => $validated["notes"] ?? "Pasien tidak hadir",
        ]);

        $queue->load(["patient", "department", "service", "servedBy"]);

        return response()->json([
            "success" => true,
            "message" => "Antrian dilewati",
            "data" => $queue,
        ]);
    }

    /**
     * Cancel a queue
     */
    public function cancel(Request $request, Queue $queue): JsonResponse
    {
        if (in_array($queue->status, ["completed", "cancelled"])) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Antrian tidak dapat dibatalkan",
                ],
                422,
            );
        }

        $validated = $request->validate([
            "notes" => ["nullable", "string"],
        ]);

        $queue->update([
            "status" => "cancelled",
            "notes" => $validated["notes"] ?? "Dibatalkan",
        ]);

        $queue->load(["patient", "department", "service", "servedBy"]);

        return response()->json([
            "success" => true,
            "message" => "Antrian dibatalkan",
            "data" => $queue,
        ]);
    }

    /**
     * Assign patient to queue
     */
    public function assignPatient(Request $request, Queue $queue): JsonResponse
    {
        $validated = $request->validate([
            "patient_id" => ["required", "exists:patients,id"],
        ]);

        $queue->update([
            "patient_id" => $validated["patient_id"],
        ]);

        $queue->load(["patient", "department", "service", "servedBy"]);

        return response()->json([
            "success" => true,
            "message" => "Pasien berhasil ditambahkan ke antrian",
            "data" => $queue,
        ]);
    }

    /**
     * Reset daily queue (admin only)
     */
    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "date" => ["required", "date"],
            "department_id" => ["nullable", "exists:departments,id"],
        ]);

        $query = Queue::where("queue_date", $validated["date"])->whereIn(
            "status",
            ["waiting", "called"],
        );

        if (isset($validated["department_id"])) {
            $query->where("department_id", $validated["department_id"]);
        }

        $count = $query->update([
            "status" => "cancelled",
            "notes" => "Reset oleh admin",
        ]);

        return response()->json([
            "success" => true,
            "message" => $count . " antrian berhasil direset",
        ]);
    }

    /**
     * Show single queue
     */
    public function show(Queue $queue): JsonResponse
    {
        $queue->load(["patient", "department", "service", "servedBy"]);

        return response()->json([
            "success" => true,
            "data" => $queue,
        ]);
    }
}
