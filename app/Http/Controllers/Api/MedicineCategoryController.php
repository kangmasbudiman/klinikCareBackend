<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicineCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MedicineCategoryController extends Controller
{
    /**
     * Get all medicine categories
     */
    public function index(Request $request): JsonResponse
    {
        $query = MedicineCategory::withCount(['medicines' => function ($q) {
            $q->where('is_active', true);
        }]);

        // Search
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $query->orderBy('name', 'asc');

        // Pagination optional
        if ($request->has('per_page')) {
            $categories = $query->paginate($request->per_page);

            return response()->json([
                'success' => true,
                'data' => $categories->items(),
                'meta' => [
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                ],
            ]);
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:medicine_categories,name'],
            'description' => ['nullable', 'string'],
        ]);

        $category = MedicineCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori obat berhasil ditambahkan',
            'data' => $category,
        ], 201);
    }

    /**
     * Display the specified category
     */
    public function show(MedicineCategory $medicineCategory): JsonResponse
    {
        $medicineCategory->loadCount(['medicines' => function ($q) {
            $q->where('is_active', true);
        }]);

        return response()->json([
            'success' => true,
            'data' => $medicineCategory,
        ]);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, MedicineCategory $medicineCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:medicine_categories,name,' . $medicineCategory->id],
            'description' => ['nullable', 'string'],
        ]);

        $medicineCategory->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori obat berhasil diperbarui',
            'data' => $medicineCategory->fresh(),
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroy(MedicineCategory $medicineCategory): JsonResponse
    {
        // Check if category has medicines
        if ($medicineCategory->medicines()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak dapat dihapus karena masih memiliki obat',
            ], 422);
        }

        $medicineCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori obat berhasil dihapus',
        ]);
    }
}
