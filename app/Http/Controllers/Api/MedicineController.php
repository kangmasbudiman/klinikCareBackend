<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class MedicineController extends Controller
{
    /**
     * Get all medicines with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Medicine::with("category");

        // Search
        if ($request->has("search") && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where("code", "like", "%{$search}%")
                    ->orWhere("name", "like", "%{$search}%")
                    ->orWhere("generic_name", "like", "%{$search}%");
            });
        }

        // Status filter
        if ($request->has("status") && $request->status !== "all") {
            $isActive = $request->status === "active";
            $query->where("is_active", $isActive);
        }

        // Category filter
        if ($request->has("category_id") && $request->category_id) {
            $query->where("category_id", $request->category_id);
        }

        // Stock status filter
        if ($request->has("stock_status") && $request->stock_status) {
            switch ($request->stock_status) {
                case "low":
                    $query->lowStock();
                    break;
                case "out_of_stock":
                    $query->whereDoesntHave("batches", function ($q) {
                        $q->available();
                    });
                    break;
            }
        }

        $query->orderBy("name", "asc");

        $perPage = $request->get("per_page", 10);
        $medicines = $query->paginate($perPage);

        return response()->json([
            "success" => true,
            "data" => $medicines->items(),
            "meta" => [
                "current_page" => $medicines->currentPage(),
                "last_page" => $medicines->lastPage(),
                "per_page" => $medicines->perPage(),
                "total" => $medicines->total(),
            ],
        ]);
    }

    /**
     * Get active medicines for dropdown
     */
    public function active(Request $request): JsonResponse
    {
        $query = Medicine::active()->with("category");

        // Search
        if ($request->has("search") && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where("name", "like", "%{$search}%")
                    ->orWhere("generic_name", "like", "%{$search}%")
                    ->orWhere("code", "like", "%{$search}%");
            });
        }

        // Category filter
        if ($request->has("category_id") && $request->category_id) {
            $query->where("category_id", $request->category_id);
        }

        $medicines = $query
            ->orderBy("name")
            ->limit(50)
            ->get([
                "id",
                "code",
                "name",
                "generic_name",
                "unit",
                "selling_price",
                "category_id",
            ]);

        // Add current stock to each medicine
        $medicines = $medicines->map(function ($medicine) {
            return array_merge($medicine->toArray(), [
                "current_stock" => $medicine->current_stock,
                "stock_status" => $medicine->stock_status,
            ]);
        });

        return response()->json([
            "success" => true,
            "data" => $medicines,
        ]);
    }

    /**
     * Get low stock medicines
     */
    public function lowStock(): JsonResponse
    {
        $medicines = Medicine::active()
            ->with("category")
            ->get()
            ->filter(function ($medicine) {
                return $medicine->current_stock <= $medicine->min_stock;
            })
            ->values();

        return response()->json([
            "success" => true,
            "data" => $medicines,
        ]);
    }

    /**
     * Get medicines with batches expiring soon
     */
    public function expiring(Request $request): JsonResponse
    {
        $months = $request->get("months", 3);

        $batches = MedicineBatch::with("medicine.category")
            ->expiringSoon($months)
            ->orderBy("expiry_date", "asc")
            ->get();

        return response()->json([
            "success" => true,
            "data" => $batches,
        ]);
    }

    /**
     * Get medicine statistics
     */
    public function stats(): JsonResponse
    {
        $total = Medicine::count();
        $active = Medicine::where("is_active", true)->count();
        $inactive = Medicine::where("is_active", false)->count();

        // Low stock count
        $lowStock = Medicine::active()
            ->get()
            ->filter(function ($medicine) {
                return $medicine->current_stock <= $medicine->min_stock &&
                    $medicine->current_stock > 0;
            })
            ->count();

        // Out of stock count
        $outOfStock = Medicine::active()
            ->get()
            ->filter(function ($medicine) {
                return $medicine->current_stock <= 0;
            })
            ->count();

        // Expiring soon count (unique medicines)
        $expiringSoon = MedicineBatch::expiringSoon(3)
            ->distinct("medicine_id")
            ->count("medicine_id");

        return response()->json([
            "success" => true,
            "data" => [
                "total" => $total,
                "active" => $active,
                "inactive" => $inactive,
                "low_stock" => $lowStock,
                "out_of_stock" => $outOfStock,
                "expiring_soon" => $expiringSoon,
            ],
        ]);
    }

    /**
     * Store a newly created medicine
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "code" => ["nullable", "string", "max:20", "unique:medicines,code"],
            "name" => ["required", "string", "max:255"],
            "generic_name" => ["nullable", "string", "max:255"],
            "category_id" => ["nullable", "exists:medicine_categories,id"],
            "unit" => ["required", "string", "max:50"],
            "unit_conversion" => ["nullable", "integer", "min:1"],
            "purchase_price" => ["nullable", "numeric", "min:0"],
            "margin_percentage" => ["nullable", "numeric", "min:0", "max:100"],
            "ppn_percentage" => ["nullable", "numeric", "min:0", "max:100"],
            "is_ppn_included" => ["nullable", "boolean"],
            "selling_price" => ["nullable", "numeric", "min:0"],
            "min_stock" => ["nullable", "integer", "min:0"],
            "max_stock" => ["nullable", "integer", "min:0"],
            "manufacturer" => ["nullable", "string", "max:100"],
            "description" => ["nullable", "string"],
            "requires_prescription" => ["nullable", "boolean"],
            "is_active" => ["nullable", "boolean"],
        ]);

        // Generate code if not provided
        if (empty($validated["code"])) {
            $validated["code"] = Medicine::generateCode();
        }

        // Default values
        $validated["is_active"] = $validated["is_active"] ?? true;
        $validated["unit_conversion"] = $validated["unit_conversion"] ?? 1;
        $validated["min_stock"] = $validated["min_stock"] ?? 10;
        $validated["max_stock"] = $validated["max_stock"] ?? 100;
        $validated["purchase_price"] = $validated["purchase_price"] ?? 0;
        $validated["margin_percentage"] = $validated["margin_percentage"] ?? 0;
        $validated["ppn_percentage"] = $validated["ppn_percentage"] ?? 11;
        $validated["is_ppn_included"] = $validated["is_ppn_included"] ?? false;
        $validated["requires_prescription"] =
            $validated["requires_prescription"] ?? false;

        // Auto calculate selling price if not provided
        if (
            empty($validated["selling_price"]) &&
            $validated["purchase_price"] > 0
        ) {
            $calculated = Medicine::calculateSellingPrice(
                $validated["purchase_price"],
                $validated["margin_percentage"],
                $validated["ppn_percentage"],
            );
            $validated["price_before_ppn"] = $calculated["price_before_ppn"];
            $validated["selling_price"] = $calculated["selling_price"];
        }

        $medicine = Medicine::create($validated);
        $medicine->load("category");

        return response()->json(
            [
                "success" => true,
                "message" => "Obat berhasil ditambahkan",
                "data" => $medicine,
            ],
            201,
        );
    }

    /**
     * Display the specified medicine with batches
     */
    public function show(Medicine $medicine): JsonResponse
    {
        $medicine->load([
            "category",
            "batches" => function ($q) {
                $q->where("current_qty", ">", 0)->orderBy("expiry_date", "asc");
            },
        ]);

        return response()->json([
            "success" => true,
            "data" => $medicine,
        ]);
    }

    /**
     * Update the specified medicine
     */
    public function update(Request $request, Medicine $medicine): JsonResponse
    {
        $validated = $request->validate([
            "code" => [
                "nullable",
                "string",
                "max:20",
                Rule::unique("medicines", "code")->ignore($medicine->id),
            ],
            "name" => ["required", "string", "max:255"],
            "generic_name" => ["nullable", "string", "max:255"],
            "category_id" => ["nullable", "exists:medicine_categories,id"],
            "unit" => ["required", "string", "max:50"],
            "unit_conversion" => ["nullable", "integer", "min:1"],
            "purchase_price" => ["nullable", "numeric", "min:0"],
            "margin_percentage" => ["nullable", "numeric", "min:0", "max:100"],
            "ppn_percentage" => ["nullable", "numeric", "min:0", "max:100"],
            "is_ppn_included" => ["nullable", "boolean"],
            "selling_price" => ["nullable", "numeric", "min:0"],
            "min_stock" => ["nullable", "integer", "min:0"],
            "max_stock" => ["nullable", "integer", "min:0"],
            "manufacturer" => ["nullable", "string", "max:100"],
            "description" => ["nullable", "string"],
            "requires_prescription" => ["nullable", "boolean"],
            "is_active" => ["nullable", "boolean"],
        ]);

        $medicine->update($validated);
        $medicine->load("category");

        return response()->json([
            "success" => true,
            "message" => "Obat berhasil diperbarui",
            "data" => $medicine,
        ]);
    }

    /**
     * Remove the specified medicine
     */
    public function destroy(Medicine $medicine): JsonResponse
    {
        // Check if medicine has batches with stock
        if ($medicine->batches()->where("current_qty", ">", 0)->exists()) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Obat tidak dapat dihapus karena masih memiliki stok",
                ],
                422,
            );
        }

        $medicine->delete();

        return response()->json([
            "success" => true,
            "message" => "Obat berhasil dihapus",
        ]);
    }

    /**
     * Toggle medicine active status
     */
    public function toggleStatus(Medicine $medicine): JsonResponse
    {
        $medicine->update([
            "is_active" => !$medicine->is_active,
        ]);

        $message = $medicine->is_active
            ? "Obat berhasil diaktifkan"
            : "Obat berhasil dinonaktifkan";

        return response()->json([
            "success" => true,
            "message" => $message,
            "data" => $medicine->fresh("category"),
        ]);
    }

    /**
     * Get stock card (kartu stok) for a medicine
     */
    public function stockCard(
        Request $request,
        Medicine $medicine,
    ): JsonResponse {
        $query = StockMovement::with(["batch", "createdBy"])->where(
            "medicine_id",
            $medicine->id,
        );

        // Date range filter
        if ($request->has("start_date")) {
            $query->whereDate("movement_date", ">=", $request->start_date);
        }
        if ($request->has("end_date")) {
            $query->whereDate("movement_date", "<=", $request->end_date);
        }

        $movements = $query
            ->orderBy("movement_date", "desc")
            ->orderBy("id", "desc")
            ->paginate($request->get("per_page", 50));

        return response()->json([
            "success" => true,
            "data" => [
                "medicine" => $medicine->load("category"),
                "movements" => $movements->items(),
            ],
            "meta" => [
                "current_page" => $movements->currentPage(),
                "last_page" => $movements->lastPage(),
                "per_page" => $movements->perPage(),
                "total" => $movements->total(),
            ],
        ]);
    }

    /**
     * Get batches for a medicine
     */
    public function batches(Medicine $medicine): JsonResponse
    {
        $batches = $medicine->batches()->orderBy("expiry_date", "asc")->get();

        return response()->json([
            "success" => true,
            "data" => $batches,
        ]);
    }

    /**
     * Get available units
     */
    public function units(): JsonResponse
    {
        $units = [
            "Tablet",
            "Kapsul",
            "Kaplet",
            "Botol",
            "Ampul",
            "Vial",
            "Tube",
            "Sachet",
            "Strip",
            "Box",
            "Piece",
            "ml",
            "gram",
        ];

        return response()->json([
            "success" => true,
            "data" => $units,
        ]);
    }

    /**
     * Calculate selling price from purchase price, margin, and PPN
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "purchase_price" => ["required", "numeric", "min:0"],
            "margin_percentage" => ["required", "numeric", "min:0", "max:100"],
            "ppn_percentage" => ["required", "numeric", "min:0", "max:100"],
        ]);

        $result = Medicine::calculateSellingPrice(
            $validated["purchase_price"],
            $validated["margin_percentage"],
            $validated["ppn_percentage"],
        );

        return response()->json([
            "success" => true,
            "data" => $result,
        ]);
    }
}
