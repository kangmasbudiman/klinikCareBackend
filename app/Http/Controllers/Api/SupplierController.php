<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    /**
     * Get all suppliers with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query();

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->status !== 'all') {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // City filter
        if ($request->has('city') && $request->city) {
            $query->where('city', $request->city);
        }

        $query->orderBy('name', 'asc');

        $perPage = $request->get('per_page', 10);
        $suppliers = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $suppliers->items(),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
            ],
        ]);
    }

    /**
     * Get active suppliers for dropdown
     */
    public function active(): JsonResponse
    {
        $suppliers = Supplier::active()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'phone', 'payment_terms']);

        return response()->json([
            'success' => true,
            'data' => $suppliers,
        ]);
    }

    /**
     * Get supplier statistics
     */
    public function stats(): JsonResponse
    {
        $total = Supplier::count();
        $active = Supplier::where('is_active', true)->count();
        $inactive = Supplier::where('is_active', false)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
            ],
        ]);
    }

    /**
     * Store a newly created supplier
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:20', 'unique:suppliers,code'],
            'name' => ['required', 'string', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'payment_terms' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        // Generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = Supplier::generateCode();
        }

        // Default values
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['payment_terms'] = $validated['payment_terms'] ?? 30;

        $supplier = Supplier::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil ditambahkan',
            'data' => $supplier,
        ], 201);
    }

    /**
     * Display the specified supplier
     */
    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $supplier,
        ]);
    }

    /**
     * Update the specified supplier
     */
    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:20', Rule::unique('suppliers', 'code')->ignore($supplier->id)],
            'name' => ['required', 'string', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'payment_terms' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $supplier->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil diperbarui',
            'data' => $supplier->fresh(),
        ]);
    }

    /**
     * Remove the specified supplier
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        // Check if supplier has related data
        if ($supplier->purchaseOrders()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier tidak dapat dihapus karena memiliki data pembelian',
            ], 422);
        }

        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil dihapus',
        ]);
    }

    /**
     * Toggle supplier active status
     */
    public function toggleStatus(Supplier $supplier): JsonResponse
    {
        $supplier->update([
            'is_active' => !$supplier->is_active,
        ]);

        $message = $supplier->is_active
            ? 'Supplier berhasil diaktifkan'
            : 'Supplier berhasil dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $supplier->fresh(),
        ]);
    }
}
