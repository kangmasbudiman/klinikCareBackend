<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    /**
     * Get all departments with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Department::query();

        // Search by code or name
        if ($request->has("search") && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where("code", "like", "%{$search}%")->orWhere(
                    "name",
                    "like",
                    "%{$search}%",
                );
            });
        }

        // Filter by status
        if ($request->has("status") && $request->status) {
            $isActive = $request->status === "active";
            $query->where("is_active", $isActive);
        }

        // Order by name
        $query->orderBy("name", "asc");

        // Pagination
        $perPage = $request->get("per_page", 10);
        $departments = $query->paginate($perPage);

        return response()->json([
            "success" => true,
            "message" => "Data departemen berhasil diambil",
            "data" => $departments->items(),
            "meta" => [
                "current_page" => $departments->currentPage(),
                "last_page" => $departments->lastPage(),
                "per_page" => $departments->perPage(),
                "total" => $departments->total(),
            ],
        ]);
    }

    /**
     * Get department statistics
     */
    public function stats(): JsonResponse
    {
        $total = Department::count();
        $active = Department::where("is_active", true)->count();
        $inactive = Department::where("is_active", false)->count();
        $totalQuota = Department::where("is_active", true)->sum(
            "quota_per_day",
        );

        return response()->json([
            "success" => true,
            "data" => [
                "total" => $total,
                "active" => $active,
                "inactive" => $inactive,
                "total_quota" => $totalQuota,
            ],
        ]);
    }

    /**
     * Get all active departments (for dropdowns)
     */
    public function active(): JsonResponse
    {
        $departments = Department::active()
            ->orderBy("name")
            ->get(["id", "code", "name", "color", "icon"]);

        return response()->json([
            "success" => true,
            "data" => $departments,
        ]);
    }

    /**
     * Get single department
     */
    public function show(Department $department): JsonResponse
    {
        $department->load("defaultService");

        return response()->json([
            "success" => true,
            "message" => "Data departemen berhasil diambil",
            "data" => $department,
        ]);
    }

    /**
     * Create new department
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
                "code" => [
                    "required",
                    "string",
                    "max:20",
                    "unique:departments,code",
                ],
                "name" => ["required", "string", "max:100"],
                "description" => ["nullable", "string"],
                "icon" => ["nullable", "string", "max:50"],
                "color" => [
                    "required",
                    "string",
                    "max:50",
                    Rule::in(array_keys(Department::COLORS)),
                ],
                "quota_per_day" => ["required", "integer", "min:1", "max:200"],
                "default_service_id" => ["nullable", "exists:services,id"],
                "is_active" => ["boolean"],
            ],
            [
                "code.required" => "Kode departemen wajib diisi",
                "code.unique" => "Kode departemen sudah digunakan",
                "code.max" => "Kode departemen maksimal 20 karakter",
                "name.required" => "Nama departemen wajib diisi",
                "name.max" => "Nama departemen maksimal 100 karakter",
                "color.required" => "Warna wajib dipilih",
                "color.in" => "Warna tidak valid",
                "quota_per_day.required" => "Kuota per hari wajib diisi",
                "quota_per_day.min" => "Kuota minimal 1",
                "quota_per_day.max" => "Kuota maksimal 200",
                "default_service_id.exists" => "Layanan default tidak valid",
            ],
        );

        // Set default is_active
        if (!isset($validated["is_active"])) {
            $validated["is_active"] = true;
        }

        $department = Department::create($validated);
        $department->load("defaultService");

        return response()->json(
            [
                "success" => true,
                "message" => "Departemen berhasil dibuat",
                "data" => $department,
            ],
            201,
        );
    }

    /**
     * Update department
     */
    public function update(
        Request $request,
        Department $department,
    ): JsonResponse {
        $validated = $request->validate(
            [
                "code" => [
                    "required",
                    "string",
                    "max:20",
                    Rule::unique("departments", "code")->ignore(
                        $department->id,
                    ),
                ],
                "name" => ["required", "string", "max:100"],
                "description" => ["nullable", "string"],
                "icon" => ["nullable", "string", "max:50"],
                "color" => [
                    "required",
                    "string",
                    "max:50",
                    Rule::in(array_keys(Department::COLORS)),
                ],
                "quota_per_day" => ["required", "integer", "min:1", "max:200"],
                "default_service_id" => ["nullable", "exists:services,id"],
                "is_active" => ["boolean"],
            ],
            [
                "code.required" => "Kode departemen wajib diisi",
                "code.unique" => "Kode departemen sudah digunakan",
                "code.max" => "Kode departemen maksimal 20 karakter",
                "name.required" => "Nama departemen wajib diisi",
                "name.max" => "Nama departemen maksimal 100 karakter",
                "color.required" => "Warna wajib dipilih",
                "color.in" => "Warna tidak valid",
                "quota_per_day.required" => "Kuota per hari wajib diisi",
                "quota_per_day.min" => "Kuota minimal 1",
                "quota_per_day.max" => "Kuota maksimal 200",
                "default_service_id.exists" => "Layanan default tidak valid",
            ],
        );

        $department->update($validated);

        return response()->json([
            "success" => true,
            "message" => "Departemen berhasil diperbarui",
            "data" => $department->fresh()->load("defaultService"),
        ]);
    }

    /**
     * Delete department
     */
    public function destroy(Department $department): JsonResponse
    {
        // TODO: Check if department is used in other tables (doctors, schedules, etc.)

        $department->delete();

        return response()->json([
            "success" => true,
            "message" => "Departemen berhasil dihapus",
        ]);
    }

    /**
     * Toggle department active status
     */
    public function toggleStatus(Department $department): JsonResponse
    {
        $department->update([
            "is_active" => !$department->is_active,
        ]);

        $message = $department->is_active
            ? "Departemen berhasil diaktifkan"
            : "Departemen berhasil dinonaktifkan";

        return response()->json([
            "success" => true,
            "message" => $message,
            "data" => $department->fresh(),
        ]);
    }

    /**
     * Get color options
     */
    public function colors(): JsonResponse
    {
        $colors = collect(Department::COLORS)
            ->map(function ($label, $value) {
                return ["value" => $value, "label" => $label];
            })
            ->values();

        return response()->json([
            "success" => true,
            "data" => $colors,
        ]);
    }

    /**
     * Get icon options
     */
    public function icons(): JsonResponse
    {
        $icons = collect(Department::ICONS)
            ->map(function ($label, $value) {
                return ["value" => $value, "label" => $label];
            })
            ->values();

        return response()->json([
            "success" => true,
            "data" => $icons,
        ]);
    }
}
