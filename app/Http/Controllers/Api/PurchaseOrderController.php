<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    /**
     * Get all purchase orders with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseOrder::with(['supplier', 'creator', 'approver', 'items.medicine']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('order_date', '<=', $request->end_date);
        }

        // Search by PO number
        if ($request->filled('search')) {
            $query->where('po_number', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->input('per_page', 15);
        $purchaseOrders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($purchaseOrders);
    }

    /**
     * Get statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => PurchaseOrder::count(),
            'draft' => PurchaseOrder::status('draft')->count(),
            'pending_approval' => PurchaseOrder::status('pending_approval')->count(),
            'approved' => PurchaseOrder::status('approved')->count(),
            'rejected' => PurchaseOrder::status('rejected')->count(),
            'ordered' => PurchaseOrder::status('ordered')->count(),
            'partial_received' => PurchaseOrder::status('partial_received')->count(),
            'completed' => PurchaseOrder::status('completed')->count(),
            'total_value_pending' => PurchaseOrder::whereIn('status', ['pending_approval', 'approved', 'ordered'])
                ->sum('total_amount'),
            'total_value_this_month' => PurchaseOrder::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Get POs pending approval
     */
    public function pendingApproval(): JsonResponse
    {
        $purchaseOrders = PurchaseOrder::with(['supplier', 'creator', 'items.medicine'])
            ->pendingApproval()
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($purchaseOrders);
    }

    /**
     * Get POs that need receiving
     */
    public function needsReceiving(): JsonResponse
    {
        $purchaseOrders = PurchaseOrder::with(['supplier', 'items.medicine'])
            ->needsReceiving()
            ->orderBy('order_date', 'asc')
            ->get();

        return response()->json($purchaseOrders);
    }

    /**
     * Store a new purchase order
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePoNumber(),
                'supplier_id' => $validated['supplier_id'],
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'created_by' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $item = new PurchaseOrderItem([
                    'medicine_id' => $itemData['medicine_id'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_percent' => $itemData['discount_percent'] ?? 0,
                    'tax_percent' => $itemData['tax_percent'] ?? 0,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                $purchaseOrder->items()->save($item);
                $item->calculateTotals();
            }

            $purchaseOrder->calculateTotals();

            DB::commit();

            return response()->json([
                'message' => 'Purchase order berhasil dibuat',
                'data' => $purchaseOrder->load(['supplier', 'items.medicine', 'creator']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membuat purchase order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a purchase order
     */
    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load([
            'supplier',
            'creator',
            'approver',
            'rejector',
            'items.medicine',
            'goodsReceipts.items',
        ]);

        return response()->json($purchaseOrder);
    }

    /**
     * Update a purchase order
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        // Only draft PO can be updated
        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Hanya PO dengan status draft yang dapat diubah',
            ], 422);
        }

        $validated = $request->validate([
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'order_date' => 'sometimes|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.id' => 'nullable|exists:purchase_order_items,id',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $purchaseOrder->update([
                'supplier_id' => $validated['supplier_id'] ?? $purchaseOrder->supplier_id,
                'order_date' => $validated['order_date'] ?? $purchaseOrder->order_date,
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? $purchaseOrder->expected_delivery_date,
                'notes' => $validated['notes'] ?? $purchaseOrder->notes,
            ]);

            if (isset($validated['items'])) {
                // Get existing item IDs
                $existingIds = collect($validated['items'])
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                // Delete removed items
                $purchaseOrder->items()
                    ->whereNotIn('id', $existingIds)
                    ->delete();

                // Update or create items
                foreach ($validated['items'] as $itemData) {
                    if (isset($itemData['id'])) {
                        $item = PurchaseOrderItem::find($itemData['id']);
                        $item->update([
                            'medicine_id' => $itemData['medicine_id'],
                            'quantity' => $itemData['quantity'],
                            'unit' => $itemData['unit'],
                            'unit_price' => $itemData['unit_price'],
                            'discount_percent' => $itemData['discount_percent'] ?? 0,
                            'tax_percent' => $itemData['tax_percent'] ?? 0,
                            'notes' => $itemData['notes'] ?? null,
                        ]);
                    } else {
                        $item = new PurchaseOrderItem([
                            'medicine_id' => $itemData['medicine_id'],
                            'quantity' => $itemData['quantity'],
                            'unit' => $itemData['unit'],
                            'unit_price' => $itemData['unit_price'],
                            'discount_percent' => $itemData['discount_percent'] ?? 0,
                            'tax_percent' => $itemData['tax_percent'] ?? 0,
                            'notes' => $itemData['notes'] ?? null,
                        ]);
                        $purchaseOrder->items()->save($item);
                    }
                    $item->calculateTotals();
                }

                $purchaseOrder->calculateTotals();
            }

            DB::commit();

            return response()->json([
                'message' => 'Purchase order berhasil diupdate',
                'data' => $purchaseOrder->fresh(['supplier', 'items.medicine', 'creator']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal mengupdate purchase order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a purchase order
     */
    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        // Only draft PO can be deleted
        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Hanya PO dengan status draft yang dapat dihapus',
            ], 422);
        }

        $purchaseOrder->items()->delete();
        $purchaseOrder->delete();

        return response()->json([
            'message' => 'Purchase order berhasil dihapus',
        ]);
    }

    /**
     * Submit PO for approval
     */
    public function submitForApproval(PurchaseOrder $purchaseOrder): JsonResponse
    {
        if (!$purchaseOrder->submitForApproval()) {
            return response()->json([
                'message' => 'Gagal mengajukan PO untuk persetujuan',
            ], 422);
        }

        return response()->json([
            'message' => 'PO berhasil diajukan untuk persetujuan',
            'data' => $purchaseOrder->fresh(['supplier', 'items.medicine']),
        ]);
    }

    /**
     * Approve PO
     */
    public function approve(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        if (!$purchaseOrder->approve(Auth::id(), $validated['notes'] ?? null)) {
            return response()->json([
                'message' => 'Gagal menyetujui PO',
            ], 422);
        }

        return response()->json([
            'message' => 'PO berhasil disetujui',
            'data' => $purchaseOrder->fresh(['supplier', 'items.medicine', 'approver']),
        ]);
    }

    /**
     * Reject PO
     */
    public function reject(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        if (!$purchaseOrder->reject(Auth::id(), $validated['reason'])) {
            return response()->json([
                'message' => 'Gagal menolak PO',
            ], 422);
        }

        return response()->json([
            'message' => 'PO berhasil ditolak',
            'data' => $purchaseOrder->fresh(['supplier', 'items.medicine', 'rejector']),
        ]);
    }

    /**
     * Mark PO as ordered
     */
    public function markAsOrdered(PurchaseOrder $purchaseOrder): JsonResponse
    {
        if (!$purchaseOrder->markAsOrdered()) {
            return response()->json([
                'message' => 'Gagal mengubah status PO menjadi dipesan',
            ], 422);
        }

        return response()->json([
            'message' => 'Status PO berhasil diubah menjadi dipesan',
            'data' => $purchaseOrder->fresh(['supplier', 'items.medicine']),
        ]);
    }

    /**
     * Cancel PO
     */
    public function cancel(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        // Can only cancel draft, pending_approval, or approved PO
        $allowedStatuses = [
            PurchaseOrder::STATUS_DRAFT,
            PurchaseOrder::STATUS_PENDING_APPROVAL,
            PurchaseOrder::STATUS_APPROVED,
        ];

        if (!in_array($purchaseOrder->status, $allowedStatuses)) {
            return response()->json([
                'message' => 'PO dengan status ini tidak dapat dibatalkan',
            ], 422);
        }

        $purchaseOrder->status = PurchaseOrder::STATUS_CANCELLED;
        $purchaseOrder->save();

        return response()->json([
            'message' => 'PO berhasil dibatalkan',
            'data' => $purchaseOrder->fresh(['supplier', 'items.medicine']),
        ]);
    }
}
