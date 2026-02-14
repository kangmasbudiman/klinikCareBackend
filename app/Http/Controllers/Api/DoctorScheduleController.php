<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Models\Queue;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DoctorScheduleController extends Controller
{
    /**
     * Display a listing of schedules.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DoctorSchedule::with(["doctor", "department"]);

        // Filter by doctor
        if ($request->has("doctor_id") && $request->doctor_id) {
            $query->forDoctor($request->doctor_id);
        }

        // Filter by department
        if ($request->has("department_id") && $request->department_id) {
            $query->forDepartment($request->department_id);
        }

        // Filter by day of week
        if (
            $request->has("day_of_week") &&
            $request->day_of_week !== "" &&
            $request->day_of_week !== null
        ) {
            $query->forDay((int) $request->day_of_week);
        }

        // Filter by active status
        if ($request->has("is_active")) {
            $query->where("is_active", $request->boolean("is_active"));
        }

        // Sort by day of week and start time
        $query->orderBy("day_of_week")->orderBy("start_time");

        // Paginate or get all
        if ($request->boolean("all")) {
            $schedules = $query->get();
            return response()->json([
                "success" => true,
                "data" => $schedules,
            ]);
        }

        $perPage = $request->get("per_page", 20);
        $schedules = $query->paginate($perPage);

        return response()->json([
            "success" => true,
            "data" => $schedules->items(),
            "meta" => [
                "current_page" => $schedules->currentPage(),
                "last_page" => $schedules->lastPage(),
                "per_page" => $schedules->perPage(),
                "total" => $schedules->total(),
            ],
        ]);
    }

    /**
     * Store a newly created schedule.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "doctor_id" => ["required", "exists:users,id"],
            "department_id" => ["required", "exists:departments,id"],
            "day_of_week" => ["required", "integer", "min:0", "max:6"],
            "start_time" => ["required", "date_format:H:i"],
            "end_time" => ["required", "date_format:H:i", "after:start_time"],
            "quota" => ["nullable", "integer", "min:1", "max:100"],
            "is_active" => ["nullable", "boolean"],
            "notes" => ["nullable", "string", "max:500"],
        ]);

        // Verify user is a doctor
        $user = User::find($validated["doctor_id"]);
        if (!$user || $user->role !== User::ROLE_DOKTER) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "User yang dipilih bukan dokter",
                ],
                422,
            );
        }

        // Check for overlapping schedule
        $overlapping = DoctorSchedule::where(
            "doctor_id",
            $validated["doctor_id"],
        )
            ->where("day_of_week", $validated["day_of_week"])
            ->where(function ($query) use ($validated) {
                $query
                    ->whereBetween("start_time", [
                        $validated["start_time"],
                        $validated["end_time"],
                    ])
                    ->orWhereBetween("end_time", [
                        $validated["start_time"],
                        $validated["end_time"],
                    ])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where(
                            "start_time",
                            "<=",
                            $validated["start_time"],
                        )->where("end_time", ">=", $validated["end_time"]);
                    });
            })
            ->exists();

        if ($overlapping) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Jadwal bertabrakan dengan jadwal yang sudah ada",
                ],
                422,
            );
        }

        $schedule = DoctorSchedule::create([
            "doctor_id" => $validated["doctor_id"],
            "department_id" => $validated["department_id"],
            "day_of_week" => $validated["day_of_week"],
            "start_time" => $validated["start_time"],
            "end_time" => $validated["end_time"],
            "quota" => $validated["quota"] ?? 20,
            "is_active" => $validated["is_active"] ?? true,
            "notes" => $validated["notes"] ?? null,
        ]);

        $schedule->load(["doctor", "department"]);

        return response()->json(
            [
                "success" => true,
                "message" => "Jadwal berhasil dibuat",
                "data" => $schedule,
            ],
            201,
        );
    }

    /**
     * Display the specified schedule.
     */
    public function show(DoctorSchedule $schedule): JsonResponse
    {
        $schedule->load(["doctor", "department"]);

        return response()->json([
            "success" => true,
            "data" => $schedule,
        ]);
    }

    /**
     * Update the specified schedule.
     */
    public function update(
        Request $request,
        DoctorSchedule $schedule,
    ): JsonResponse {
        $validated = $request->validate([
            "doctor_id" => ["sometimes", "exists:users,id"],
            "department_id" => ["sometimes", "exists:departments,id"],
            "day_of_week" => ["sometimes", "integer", "min:0", "max:6"],
            "start_time" => ["sometimes", "date_format:H:i"],
            "end_time" => ["sometimes", "date_format:H:i"],
            "quota" => ["nullable", "integer", "min:1", "max:100"],
            "is_active" => ["nullable", "boolean"],
            "notes" => ["nullable", "string", "max:500"],
        ]);

        // If updating doctor, verify it's a doctor
        if (isset($validated["doctor_id"])) {
            $user = User::find($validated["doctor_id"]);
            if (!$user || $user->role !== User::ROLE_DOKTER) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "User yang dipilih bukan dokter",
                    ],
                    422,
                );
            }
        }

        // Check time range validity
        $startTime = $validated["start_time"] ?? $schedule->start_time;
        $endTime = $validated["end_time"] ?? $schedule->end_time;

        if (strtotime($endTime) <= strtotime($startTime)) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Waktu selesai harus lebih besar dari waktu mulai",
                ],
                422,
            );
        }

        // Check for overlapping schedule (excluding current)
        $doctorId = $validated["doctor_id"] ?? $schedule->doctor_id;
        $dayOfWeek = $validated["day_of_week"] ?? $schedule->day_of_week;

        $overlapping = DoctorSchedule::where("doctor_id", $doctorId)
            ->where("day_of_week", $dayOfWeek)
            ->where("id", "!=", $schedule->id)
            ->where(function ($query) use ($startTime, $endTime) {
                $query
                    ->whereBetween("start_time", [$startTime, $endTime])
                    ->orWhereBetween("end_time", [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where("start_time", "<=", $startTime)->where(
                            "end_time",
                            ">=",
                            $endTime,
                        );
                    });
            })
            ->exists();

        if ($overlapping) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Jadwal bertabrakan dengan jadwal yang sudah ada",
                ],
                422,
            );
        }

        $schedule->update($validated);
        $schedule->load(["doctor", "department"]);

        return response()->json([
            "success" => true,
            "message" => "Jadwal berhasil diperbarui",
            "data" => $schedule,
        ]);
    }

    /**
     * Remove the specified schedule.
     */
    public function destroy(DoctorSchedule $schedule): JsonResponse
    {
        $schedule->delete();

        return response()->json([
            "success" => true,
            "message" => "Jadwal berhasil dihapus",
        ]);
    }

    /**
     * Get available doctors for today or specific day.
     */
    public function availableDoctors(Request $request): JsonResponse
    {
        $dayOfWeek = $request->has("day")
            ? (int) $request->day
            : now()->dayOfWeek;

        $query = DoctorSchedule::with(["doctor", "department"])
            ->active()
            ->forDay($dayOfWeek);

        // Filter by department
        if ($request->has("department_id") && $request->department_id) {
            $query->forDepartment($request->department_id);
        }

        $schedules = $query->orderBy("start_time")->get();

        // Group by doctor
        $doctors = $schedules
            ->groupBy("doctor_id")
            ->map(function ($doctorSchedules) {
                $firstSchedule = $doctorSchedules->first();
                return [
                    "doctor" => $firstSchedule->doctor,
                    "schedules" => $doctorSchedules
                        ->map(function ($schedule) {
                            return [
                                "id" => $schedule->id,
                                "department" => $schedule->department,
                                "day_of_week" => $schedule->day_of_week,
                                "day_label" => $schedule->day_label,
                                "start_time" => $schedule->start_time,
                                "end_time" => $schedule->end_time,
                                "time_range" => $schedule->time_range,
                                "quota" => $schedule->quota,
                                "is_currently_available" => $schedule->isCurrentlyAvailable(),
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        return response()->json([
            "success" => true,
            "data" => $doctors,
            "day_of_week" => $dayOfWeek,
            "day_label" => DoctorSchedule::DAY_LABELS[$dayOfWeek] ?? "",
        ]);
    }

    /**
     * Get schedule for a specific doctor.
     */
    public function doctorSchedule(
        Request $request,
        int $doctorId,
    ): JsonResponse {
        $doctor = User::where("id", $doctorId)
            ->where("role", User::ROLE_DOKTER)
            ->first();

        if (!$doctor) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Dokter tidak ditemukan",
                ],
                404,
            );
        }

        $schedules = DoctorSchedule::with("department")
            ->forDoctor($doctorId)
            ->active()
            ->orderBy("day_of_week")
            ->orderBy("start_time")
            ->get();

        // Group by day
        $groupedSchedules = $schedules
            ->groupBy("day_of_week")
            ->map(function ($daySchedules, $day) {
                return [
                    "day_of_week" => (int) $day,
                    "day_label" => DoctorSchedule::DAY_LABELS[$day] ?? "",
                    "schedules" => $daySchedules
                        ->map(function ($schedule) {
                            return [
                                "id" => $schedule->id,
                                "department" => $schedule->department,
                                "start_time" => $schedule->start_time,
                                "end_time" => $schedule->end_time,
                                "time_range" => $schedule->time_range,
                                "quota" => $schedule->quota,
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        return response()->json([
            "success" => true,
            "data" => [
                "doctor" => $doctor,
                "schedules" => $groupedSchedules,
            ],
        ]);
    }

    /**
     * Get day options for dropdown.
     */
    public function dayOptions(): JsonResponse
    {
        return response()->json([
            "success" => true,
            "data" => DoctorSchedule::getDayOptions(),
        ]);
    }

    /**
     * Toggle schedule active status.
     */
    public function toggleStatus(DoctorSchedule $schedule): JsonResponse
    {
        $schedule->update([
            "is_active" => !$schedule->is_active,
        ]);

        $schedule->load(["doctor", "department"]);

        return response()->json([
            "success" => true,
            "message" => $schedule->is_active
                ? "Jadwal diaktifkan"
                : "Jadwal dinonaktifkan",
            "data" => $schedule,
        ]);
    }

    /**
     * Get today's doctor schedules for a department (public/kiosk endpoint).
     */
    public function kioskSchedules(int $departmentId): JsonResponse
    {
        $department = Department::find($departmentId);

        if (!$department || !$department->is_active) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Departemen tidak ditemukan atau tidak aktif",
                ],
                404,
            );
        }

        $today = now()->toDateString();
        $dayOfWeek = now()->dayOfWeek;

        $schedules = DoctorSchedule::with(["doctor:id,name,role"])
            ->active()
            ->forDay($dayOfWeek)
            ->forDepartment($departmentId)
            ->orderBy("start_time")
            ->get();

        // Count today's active queues per doctor in this department
        $queueCounts = Queue::where("department_id", $departmentId)
            ->where("queue_date", $today)
            ->whereNotNull("doctor_id")
            ->whereNotIn("status", ["cancelled"])
            ->selectRaw("doctor_id, COUNT(*) as total")
            ->groupBy("doctor_id")
            ->pluck("total", "doctor_id");

        $data = $schedules
            ->map(function ($schedule) use ($queueCounts) {
                $usedQuota = $queueCounts->get($schedule->doctor_id, 0);
                $remainingQuota = max(0, $schedule->quota - $usedQuota);

                return [
                    "id" => $schedule->id,
                    "doctor" => [
                        "id" => $schedule->doctor->id,
                        "name" => $schedule->doctor->name,
                    ],
                    "day_of_week" => $schedule->day_of_week,
                    "day_label" => $schedule->day_label,
                    "start_time" => $schedule->start_time,
                    "end_time" => $schedule->end_time,
                    "time_range" => $schedule->time_range,
                    "quota" => $schedule->quota,
                    "remaining_quota" => $remainingQuota,
                    "is_currently_available" => $schedule->isCurrentlyAvailable(),
                ];
            })
            ->filter(fn($s) => $s["remaining_quota"] > 0)
            ->values();

        return response()->json([
            "success" => true,
            "data" => $data,
            "department" => [
                "id" => $department->id,
                "name" => $department->name,
                "color" => $department->color,
            ],
            "day_of_week" => $dayOfWeek,
            "day_label" => DoctorSchedule::DAY_LABELS[$dayOfWeek] ?? "",
        ]);
    }
}
