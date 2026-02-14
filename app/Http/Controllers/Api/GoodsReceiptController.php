<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GoodsReceiptController extends Controller
{
    /**
     * Get all goods receipts with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = GoodsReceipt::with(['supplier', 'purchaseOrder', 'receiver', 'items.medicine']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by PO
        if ($request->filled('purchase_order_id')) {
            $query->where('purchase_order_id', $request->purchase_order_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('receipt_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('receipt_date', '<=', $request->end_date);
        }

        // Search by receipt number
        if ($request->filled('search')) {
            $query->where('receipt_number', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->input('per_page', 15);
        $goodsReceipts = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($goodsReceipts);
    }

    /**
     * Get statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => GoodsReceipt::count(),
            'draft' => GoodsReceipt::status('draft')->count(),
            'completed' => GoodsReceipt::status('completed')->count(),
            'total_value_this_month' => GoodsReceipt::completed()
                ->whereMonth('receipt_date', now()->month)
                ->whereYear('receipt_date', now()->year)
                ->sum('total_amount'),
            'total_today' => GoodsReceipt::completed()
                ->whereDate('receipt_date', now()->toDateString())
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Store a new goods receipt
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'receipt_date' => 'required|date',
            'supplier_invoice_number' => 'nullable|string|max:100',
            'supplier_invoice_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'nullable|exists:purchase_order_items,id',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.batch_number' => 'required|string|max:50',
            'items.*.expiry_date' => 'required|date|after:today',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $goodsReceipt = GoodsReceipt::create([
                'receipt_number' => GoodsReceipt::generateReceiptNumber(),
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'supplier_id' => $validated['supplier_id'],
                'receipt_date' => $validated['receipt_date'],
                'supplier_invoice_number' => $validated['supplier_invoice_number'] ?? null,
                'supplier_invoice_date' => $validated['supplier_invoice_date'] ?? null,
                'status' => GoodsReceipt::STATUS_DRAFT,
                'received_by' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $item = new GoodsReceiptItem([
                    'purchase_order_item_id' => $itemData['purchase_order_item_id'] ?? null,
                    'medicine_id' => $itemData['medicine_id'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['quantity'] * $itemData['unit_price'],
                    'batch_number' => $itemData['batch_number'],
                    'expiry_date' => $itemData['expiry_date'],
                    'notes' => $itemData['notes'] ?? null,
                ]);

                $goodsReceipt->items()->save($item);
            }

            $goodsReceipt->calculateTotal();

            DB::commit();

            return response()->json([
                'message' => 'Penerimaan barang berhasil dibuat',
                'data' => $goodsReceipt->load(['supplier', 'purchaseOrder', 'items.medicine', 'receiver']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membuat penerimaan barang',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create goods receipt from PO
     */
    public function createFromPo(PurchaseOrder $purchaseOrder): JsonResponse
    {
        // Check PO status
        $allowedStatuses = [
            PurchaseOrder::STATUS_ORDERED,
            PurchaseOrder::STATUS_PARTIAL_RECEIVED,
        ];

        if (!in_array($purchaseOrder->status, $allowedStatuses)) {
            return response()->json([
                'message' => 'PO dengan status ini tidak dapat diterima',
            ], 422);
        }

        // Get remaining items to receive
        $items = $purchaseOrder->items()
            ->with('medicine')
            ->get()
            ->filter(fn($item) => $item->remaining_quantity > 0)
            ->map(fn($item) => [
                'purchase_order_item_id' => $item->id,
                'medicine_id' => $item->medicine_id,
                'medicine' => $item->medicine,
                'quantity' => $item->remaining_quantity,
                'unit' => $item->unit,
                'unit_price' => $item->unit_price,
                'batch_number' => '',
                'expiry_date' => null,
            ])
            ->values();

        return response()->json([
            'purchase_order' => $purchaseOrder->load('supplier'),
            'items' => $items,
        ]);
    }

    /**
     * Show a goods receipt
     */
    public function show(GoodsReceipt $goodsReceipt): JsonResponse
    {
        $goodsReceipt->load([
            'supplier',
            'purchaseOrder',
            'receiver',
            'items.medicine',
            'items.medicineBatch',
            'items.purchaseOrderItem',
        ]);

        return response()->json($goodsReceipt);
    }

    /**
     * Update a goods receipt
     */
    public function update(Request $request, GoodsReceipt $goodsReceipt): JsonResponse
    {
        // Only draft can be updated
        if ($goodsReceipt->status !== GoodsReceipt::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Hanya penerimaan dengan status draft yang dapat diubah',
            ], 422);
        }

        $validated = $request->validate([
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'receipt_date' => 'sometimes|date',
            'supplier_invoice_number' => 'nullable|string|max:100',
            'supplier_invoice_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.id' => 'nullable|exists:goods_receipt_items,id',
            'items.*.purchase_order_item_id' => 'nullable|exists:purchase_order_items,id',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.batch_number' => 'required|string|max:50',
            'items.*.expiry_date' => 'required|date|after:today',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $goodsReceipt->update([
                'supplier_id' => $validated['supplier_id'] ?? $goodsReceipt->supplier_id,
                'receipt_date' => $validated['receipt_date'] ?? $goodsReceipt->receipt_date,
                'supplier_invoice_number' => $validated['supplier_invoice_number'] ?? $goodsReceipt->supplier_invoice_number,
                'supplier_invoice_date' => $validated['supplier_invoice_date'] ?? $goodsReceipt->supplier_invoice_date,
                'notes' => $validated['notes'] ?? $goodsReceipt->notes,
            ]);

            if (isset($validated['items'])) {
                // Get existing item IDs
                $existingIds = collect($validated['items'])
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                // Delete removed items
                $goodsReceipt->items()
                    ->whereNotIn('id', $existingIds)
                    ->delete();

                // Update or create items
                foreach ($validated['items'] as $itemData) {
                    if (isset($itemData['id'])) {
                        $item = GoodsReceiptItem::find($itemData['id']);
                        $item->update([
                            'purchase_order_item_id' => $itemData['purchase_order_item_id'] ?? null,
                            'medicine_id' => $itemData['medicine_id'],
                            'quantity' => $itemData['quantity'],
                            'unit' => $itemData['unit'],
                            'unit_price' => $itemData['unit_price'],
                            'total_price' => $itemData['quantity'] * $itemData['unit_price'],
                            'batch_number' => $itemData['batch_number'],
                            'expiry_date' => $itemData['expiry_date'],
                            'notes' => $itemData['notes'] ?? null,
                        ]);
                    } else {
                        $goodsReceipt->items()->create([
                            'purchase_order_item_id' => $itemData['purchase_order_item_id'] ?? null,
                            'medicine_id' => $itemData['medicine_id'],
                            'quantity' => $itemData['quantity'],
                            'unit' => $itemData['unit'],
                            'unit_price' => $itemData['unit_price'],
                            'total_price' => $itemData['quantity'] * $itemData['unit_price'],
                            'batch_number' => $itemData['batch_number'],
                            'expiry_date' => $itemData['expiry_date'],
                            'notes' => $itemData['notes'] ?? null,
                        ]);
                    }
                }

                $goodsReceipt->calculateTotal();
            }

            DB::commit();

            return response()->json([
                'message' => 'Penerimaan barang berhasil diupdate',
                'data' => $goodsReceipt->fresh(['supplier', 'purchaseOrder', 'items.medicine', 'receiver']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal mengupdate penerimaan barang',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a goods receipt
     */
    public function destroy(GoodsReceipt $goodsReceipt): JsonResponse
    {
        // Only draft can be deleted
        if ($goodsReceipt->status !== GoodsReceipt::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Hanya penerimaan dengan status draft yang dapat dihapus',
            ], 422);
        }

        $goodsReceipt->items()->delete();
        $goodsReceipt->delete();

        return response()->json([
            'message' => 'Penerimaan barang berhasil dihapus',
        ]);
    }

    /**
     * Complete goods receipt - create batches and stock movements
     */
    public function complete(GoodsReceipt $goodsReceipt): JsonResponse
    {
        try {
            if (!$goodsReceipt->complete()) {
                return response()->json([
                    'message' => 'Gagal menyelesaikan penerimaan barang',
                ], 422);
            }

            return response()->json([
                'message' => 'Penerimaan barang berhasil diselesaikan. Stok telah diupdate.',
                'data' => $goodsReceipt->fresh(['supplier', 'purchaseOrder', 'items.medicine', 'items.medicineBatch']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyelesaikan penerimaan barang',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel goods receipt
     */
    public function cancel(GoodsReceipt $goodsReceipt): JsonResponse
    {
        // Only draft can be cancelled
        if ($goodsReceipt->status !== GoodsReceipt::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Hanya penerimaan dengan status draft yang dapat dibatalkan',
            ], 422);
        }

        $goodsReceipt->status = GoodsReceipt::STATUS_CANCELLED;
        $goodsReceipt->save();

        return response()->json([
            'message' => 'Penerimaan barang berhasil dibatalkan',
            'data' => $goodsReceipt->fresh(['supplier', 'items.medicine']),
        ]);
    }
}
