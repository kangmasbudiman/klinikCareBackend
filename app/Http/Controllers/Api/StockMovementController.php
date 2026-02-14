<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockMovementController extends Controller
{
    /**
     * Get all stock movements with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockMovement::with(['medicine', 'medicineBatch', 'creator']);

        // Filter by medicine
        if ($request->filled('medicine_id')) {
            $query->where('medicine_id', $request->medicine_id);
        }

        // Filter by batch
        if ($request->filled('batch_id')) {
            $query->where('medicine_batch_id', $request->batch_id);
        }

        // Filter by movement type
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        // Filter by reason
        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('movement_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('movement_date', '<=', $request->end_date);
        }

        // Search by movement number
        if ($request->filled('search')) {
            $query->where('movement_number', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->input('per_page', 15);
        $movements = $query->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json($movements);
    }

    /**
     * Get statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now()->endOfMonth());

        $stats = [
            'total_in' => StockMovement::stockIn()
                ->byDateRange($startDate, $endDate)
                ->sum('quantity'),
            'total_out' => StockMovement::stockOut()
                ->byDateRange($startDate, $endDate)
                ->sum('quantity'),
            'movements_today' => StockMovement::whereDate('movement_date', now()->toDateString())
                ->count(),
            'by_reason' => StockMovement::byDateRange($startDate, $endDate)
                ->select('reason', DB::raw('SUM(quantity) as total_quantity'), DB::raw('COUNT(*) as count'))
                ->groupBy('reason')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Get stock card for a medicine
     */
    public function stockCard(Request $request, Medicine $medicine): JsonResponse
    {
        $query = StockMovement::with(['medicineBatch', 'creator'])
            ->where('medicine_id', $medicine->id);

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('movement_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('movement_date', '<=', $request->end_date);
        }

        $movements = $query->orderBy('movement_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'medicine' => $medicine->load('category'),
            'current_stock' => $medicine->current_stock,
            'movements' => $movements,
        ]);
    }

    /**
     * Show a stock movement
     */
    public function show(StockMovement $stockMovement): JsonResponse
    {
        $stockMovement->load(['medicine', 'medicineBatch', 'creator']);

        return response()->json($stockMovement);
    }

    /**
     * Create manual stock adjustment
     */
    public function adjustment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medicine_id' => 'required|exists:medicines,id',
            'medicine_batch_id' => 'nullable|exists:medicine_batches,id',
            'adjustment_type' => 'required|in:plus,minus',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|in:adjustment_plus,adjustment_minus,expired,damage,return_supplier,return_patient,initial_stock,other',
            'notes' => 'nullable|string',
        ]);

        $medicine = Medicine::findOrFail($validated['medicine_id']);
        $batch = $validated['medicine_batch_id']
            ? MedicineBatch::findOrFail($validated['medicine_batch_id'])
            : null;

        // Validate stock for minus adjustment
        if ($validated['adjustment_type'] === 'minus') {
            if ($batch) {
                if ($batch->current_qty < $validated['quantity']) {
                    return response()->json([
                        'message' => 'Jumlah pengurangan melebihi stok batch yang tersedia',
                    ], 422);
                }
            } else {
                if ($medicine->current_stock < $validated['quantity']) {
                    return response()->json([
                        'message' => 'Jumlah pengurangan melebihi stok yang tersedia',
                    ], 422);
                }
            }
        }

        DB::beginTransaction();

        try {
            $stockBefore = $medicine->current_stock;
            $movementType = $validated['adjustment_type'] === 'plus' ? 'in' : 'out';

            // Determine reason based on adjustment type if not specific
            $reason = $validated['reason'];
            if ($reason === 'adjustment_plus' && $validated['adjustment_type'] === 'minus') {
                $reason = 'adjustment_minus';
            } elseif ($reason === 'adjustment_minus' && $validated['adjustment_type'] === 'plus') {
                $reason = 'adjustment_plus';
            }

            $movement = StockMovement::create([
                'movement_number' => StockMovement::generateMovementNumber(),
                'medicine_id' => $medicine->id,
                'medicine_batch_id' => $batch?->id,
                'movement_type' => $movementType,
                'reason' => $reason,
                'quantity' => $validated['quantity'],
                'unit' => $medicine->unit,
                'stock_before' => $stockBefore,
                'stock_after' => $movementType === 'in'
                    ? $stockBefore + $validated['quantity']
                    : $stockBefore - $validated['quantity'],
                'reference_type' => 'adjustment',
                'reference_id' => null,
                'created_by' => Auth::id(),
                'notes' => $validated['notes'],
                'movement_date' => now(),
            ]);

            // Update batch stock if specified
            if ($batch) {
                if ($movementType === 'in') {
                    $batch->addStock($validated['quantity']);
                } else {
                    $batch->reduceStock($validated['quantity']);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Penyesuaian stok berhasil',
                'data' => $movement->load(['medicine', 'medicineBatch', 'creator']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal melakukan penyesuaian stok',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get movement reasons
     */
    public function reasons(): JsonResponse
    {
        $reasons = [
            ['value' => 'purchase', 'label' => 'Pembelian', 'type' => 'in'],
            ['value' => 'sales', 'label' => 'Penjualan/Penyerahan', 'type' => 'out'],
            ['value' => 'adjustment_plus', 'label' => 'Penyesuaian (+)', 'type' => 'in'],
            ['value' => 'adjustment_minus', 'label' => 'Penyesuaian (-)', 'type' => 'out'],
            ['value' => 'return_supplier', 'label' => 'Retur ke Supplier', 'type' => 'out'],
            ['value' => 'return_patient', 'label' => 'Retur dari Pasien', 'type' => 'in'],
            ['value' => 'expired', 'label' => 'Kadaluarsa', 'type' => 'out'],
            ['value' => 'damage', 'label' => 'Rusak/Pecah', 'type' => 'out'],
            ['value' => 'transfer_in', 'label' => 'Mutasi Masuk', 'type' => 'in'],
            ['value' => 'transfer_out', 'label' => 'Mutasi Keluar', 'type' => 'out'],
            ['value' => 'initial_stock', 'label' => 'Stok Awal', 'type' => 'in'],
            ['value' => 'other', 'label' => 'Lainnya', 'type' => 'both'],
        ];

        return response()->json($reasons);
    }

    /**
     * Get movement summary report
     */
    public function summary(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now()->endOfMonth());

        $summary = Medicine::with('category')
            ->get()
            ->map(function ($medicine) use ($startDate, $endDate) {
                $movements = StockMovement::where('medicine_id', $medicine->id)
                    ->byDateRange($startDate, $endDate)
                    ->get();

                return [
                    'medicine' => $medicine,
                    'opening_stock' => $movements->first()?->stock_before ?? $medicine->current_stock,
                    'total_in' => $movements->where('movement_type', 'in')->sum('quantity'),
                    'total_out' => $movements->where('movement_type', 'out')->sum('quantity'),
                    'closing_stock' => $medicine->current_stock,
                ];
            });

        return response()->json([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => $summary,
        ]);
    }
}
