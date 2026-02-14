<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    /**
     * Get all services with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Service::with("department:id,code,name,color");

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

        // Filter by category
        if ($request->has("category") && $request->category) {
            $query->where("category", $request->category);
        }

        // Filter by department
        if ($request->has("department_id") && $request->department_id) {
            $query->where("department_id", $request->department_id);
        }

        // Order by name
        $query->orderBy("category", "asc")->orderBy("name", "asc");

        // Pagination
        $perPage = $request->get("per_page", 10);
        $services = $query->paginate($perPage);

        // Add computed attributes
        $items = collect($services->items())->map(function ($service) {
            return array_merge($service->toArray(), [
                "total_price" => $service->total_price,
                "category_label" => $service->category_label,
                "formatted_base_price" => $service->formatted_base_price,
                "formatted_total_price" => $service->formatted_total_price,
            ]);
        });

        return response()->json([
            "success" => true,
            "message" => "Data layanan berhasil diambil",
            "data" => $items,
            "meta" => [
                "current_page" => $services->currentPage(),
                "last_page" => $services->lastPage(),
                "per_page" => $services->perPage(),
                "total" => $services->total(),
            ],
        ]);
    }

    /**
     * Get service statistics
     */
    public function stats(): JsonResponse
    {
        $total = Service::count();
        $active = Service::where("is_active", true)->count();
        $inactive = Service::where("is_active", false)->count();

        // Count by category
        $byCategory = Service::selectRaw("category, count(*) as count")
            ->groupBy("category")
            ->pluck("count", "category");

        return response()->json([
            "success" => true,
            "data" => [
                "total" => $total,
                "active" => $active,
                "inactive" => $inactive,
                "by_category" => $byCategory,
            ],
        ]);
    }

    /**
     * Get all active services (for dropdowns)
     */
    public function active(Request $request): JsonResponse
    {
        $query = Service::active()->orderBy("name");

        // Optional category filter
        if ($request->has("category") && $request->category) {
            $query->where("category", $request->category);
        }

        // Optional department filter
        if ($request->has("department_id") && $request->department_id) {
            $query->where("department_id", $request->department_id);
        }

        $services = $query->get([
            "id",
            "code",
            "name",
            "category",
            "base_price",
            "doctor_fee",
            "hospital_fee",
            "color",
            "icon",
        ]);

        // Add computed attributes
        $services = $services->map(function ($service) {
            return array_merge($service->toArray(), [
                "total_price" => $service->total_price,
                "category_label" => $service->category_label,
            ]);
        });

        return response()->json([
            "success" => true,
            "data" => $services,
        ]);
    }

    /**
     * Get single service
     */
    public function show(Service $service): JsonResponse
    {
        $service->load("department:id,code,name,color");

        return response()->json([
            "success" => true,
            "message" => "Data layanan berhasil diambil",
            "data" => array_merge($service->toArray(), [
                "total_price" => $service->total_price,
                "category_label" => $service->category_label,
                "formatted_base_price" => $service->formatted_base_price,
                "formatted_total_price" => $service->formatted_total_price,
            ]),
        ]);
    }

    /**
     * Create new service
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
                "code" => [
                    "required",
                    "string",
                    "max:20",
                    "unique:services,code",
                ],
                "name" => ["required", "string", "max:100"],
                "description" => ["nullable", "string"],
                "category" => [
                    "required",
                    "string",
                    Rule::in(array_keys(Service::CATEGORIES)),
                ],
                "department_id" => ["nullable", "exists:departments,id"],
                "base_price" => ["required", "numeric", "min:0"],
                "doctor_fee" => ["nullable", "numeric", "min:0"],
                "hospital_fee" => ["nullable", "numeric", "min:0"],
                "duration" => ["required", "integer", "min:5", "max:480"],
                "is_active" => ["boolean"],
                "requires_appointment" => ["boolean"],
                "icon" => ["nullable", "string", "max:50"],
                "color" => [
                    "nullable",
                    "string",
                    "max:20",
                    Rule::in(array_keys(Service::COLORS)),
                ],
            ],
            [
                "code.required" => "Kode layanan wajib diisi",
                "code.unique" => "Kode layanan sudah digunakan",
                "code.max" => "Kode layanan maksimal 20 karakter",
                "name.required" => "Nama layanan wajib diisi",
                "name.max" => "Nama layanan maksimal 100 karakter",
                "category.required" => "Kategori wajib dipilih",
                "category.in" => "Kategori tidak valid",
                "base_price.required" => "Tarif dasar wajib diisi",
                "base_price.min" => "Tarif dasar minimal 0",
                "duration.required" => "Durasi wajib diisi",
                "duration.min" => "Durasi minimal 5 menit",
                "duration.max" => "Durasi maksimal 480 menit (8 jam)",
            ],
        );

        // Set defaults
        $validated["is_active"] = $validated["is_active"] ?? true;
        $validated["requires_appointment"] =
            $validated["requires_appointment"] ?? false;
        $validated["doctor_fee"] = $validated["doctor_fee"] ?? 0;
        $validated["hospital_fee"] = $validated["hospital_fee"] ?? 0;

        $service = Service::create($validated);
        $service->load("department:id,code,name,color");

        return response()->json(
            [
                "success" => true,
                "message" => "Layanan berhasil dibuat",
                "data" => array_merge($service->toArray(), [
                    "total_price" => $service->total_price,
                    "category_label" => $service->category_label,
                ]),
            ],
            201,
        );
    }

    /**
     * Update service
     */
    public function update(Request $request, Service $service): JsonResponse
    {
        $validated = $request->validate(
            [
                "code" => [
                    "required",
                    "string",
                    "max:20",
                    Rule::unique("services", "code")->ignore($service->id),
                ],
                "name" => ["required", "string", "max:100"],
                "description" => ["nullable", "string"],
                "category" => [
                    "required",
                    "string",
                    Rule::in(array_keys(Service::CATEGORIES)),
                ],
                "department_id" => ["nullable", "exists:departments,id"],
                "base_price" => ["required", "numeric", "min:0"],
                "doctor_fee" => ["nullable", "numeric", "min:0"],
                "hospital_fee" => ["nullable", "numeric", "min:0"],
                "duration" => ["required", "integer", "min:5", "max:480"],
                "is_active" => ["boolean"],
                "requires_appointment" => ["boolean"],
                "icon" => ["nullable", "string", "max:50"],
                "color" => [
                    "nullable",
                    "string",
                    "max:20",
                    Rule::in(array_keys(Service::COLORS)),
                ],
            ],
            [
                "code.required" => "Kode layanan wajib diisi",
                "code.unique" => "Kode layanan sudah digunakan",
                "code.max" => "Kode layanan maksimal 20 karakter",
                "name.required" => "Nama layanan wajib diisi",
                "name.max" => "Nama layanan maksimal 100 karakter",
                "category.required" => "Kategori wajib dipilih",
                "category.in" => "Kategori tidak valid",
                "base_price.required" => "Tarif dasar wajib diisi",
                "base_price.min" => "Tarif dasar minimal 0",
                "duration.required" => "Durasi wajib diisi",
                "duration.min" => "Durasi minimal 5 menit",
                "duration.max" => "Durasi maksimal 480 menit (8 jam)",
            ],
        );

        $service->update($validated);
        $service->load("department:id,code,name,color");

        return response()->json([
            "success" => true,
            "message" => "Layanan berhasil diperbarui",
            "data" => array_merge($service->fresh()->toArray(), [
                "total_price" => $service->total_price,
                "category_label" => $service->category_label,
            ]),
        ]);
    }

    /**
     * Delete service
     */
    public function destroy(Service $service): JsonResponse
    {
        // TODO: Check if service is used in transactions, invoices, etc.

        $service->delete();

        return response()->json([
            "success" => true,
            "message" => "Layanan berhasil dihapus",
        ]);
    }

    /**
     * Toggle service active status
     */
    public function toggleStatus(Service $service): JsonResponse
    {
        $service->update([
            "is_active" => !$service->is_active,
        ]);

        $message = $service->is_active
            ? "Layanan berhasil diaktifkan"
            : "Layanan berhasil dinonaktifkan";

        return response()->json([
            "success" => true,
            "message" => $message,
            "data" => $service->fresh(),
        ]);
    }

    /**
     * Get category options
     */
    public function categories(): JsonResponse
    {
        $categories = collect(Service::CATEGORIES)
            ->map(function ($label, $value) {
                return ["value" => $value, "label" => $label];
            })
            ->values();

        return response()->json([
            "success" => true,
            "data" => $categories,
        ]);
    }

    /**
     * Get color options
     */
    public function colors(): JsonResponse
    {
        $colors = collect(Service::COLORS)
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
        $icons = collect(Service::ICONS)
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
