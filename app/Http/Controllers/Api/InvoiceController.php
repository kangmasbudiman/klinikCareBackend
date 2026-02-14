<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with([
            "patient",
            "medicalRecord.department",
            "cashier",
            "items",
        ]);

        // Date range filter
        if ($request->has("start_date") && $request->has("end_date")) {
            $query
                ->whereDate("created_at", ">=", $request->start_date)
                ->whereDate("created_at", "<=", $request->end_date);
        } elseif ($request->has("date")) {
            // Single date filter (backward compatible)
            $query->whereDate("created_at", $request->date);
        }

        // Payment status filter
        if ($request->has("payment_status") && $request->payment_status) {
            $query->where("payment_status", $request->payment_status);
        }

        // Payment method filter
        if ($request->has("payment_method") && $request->payment_method) {
            $query->where("payment_method", $request->payment_method);
        }

        // Search
        if ($request->has("search") && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where("invoice_number", "like", "%{$search}%")->orWhereHas(
                    "patient",
                    function ($q2) use ($search) {
                        $q2->where("name", "like", "%{$search}%")->orWhere(
                            "medical_record_number",
                            "like",
                            "%{$search}%",
                        );
                    },
                );
            });
        }

        $query->orderBy("created_at", "desc");

        $perPage = $request->get("per_page", 20);
        $invoices = $query->paginate($perPage);

        return response()->json([
            "success" => true,
            "data" => $invoices->items(),
            "meta" => [
                "current_page" => $invoices->currentPage(),
                "last_page" => $invoices->lastPage(),
                "per_page" => $invoices->perPage(),
                "total" => $invoices->total(),
            ],
        ]);
    }

    /**
     * Get unpaid invoices
     */
    public function unpaid(Request $request): JsonResponse
    {
        $query = Invoice::with(["patient", "medicalRecord.department", "items"])
            ->where("payment_status", "unpaid")
            ->orderBy("created_at", "asc");

        // Date filter
        if ($request->has("date")) {
            $query->whereDate("created_at", $request->date);
        }

        $invoices = $query->get();

        return response()->json([
            "success" => true,
            "data" => $invoices,
        ]);
    }

    /**
     * Get invoice statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $query = Invoice::query();

        // Date range filter
        if ($request->has("start_date") && $request->has("end_date")) {
            $query
                ->whereDate("created_at", ">=", $request->start_date)
                ->whereDate("created_at", "<=", $request->end_date);
        } elseif ($request->has("date")) {
            // Single date filter (backward compatible)
            $query->whereDate("created_at", $request->date);
        } else {
            // Default to today
            $query->whereDate("created_at", now()->toDateString());
        }

        $total = (clone $query)->count();
        $unpaid = (clone $query)->where("payment_status", "unpaid")->count();
        $paid = (clone $query)->where("payment_status", "paid")->count();

        $totalRevenue = (clone $query)
            ->where("payment_status", "paid")
            ->sum("total_amount");
        $totalUnpaid = (clone $query)
            ->where("payment_status", "unpaid")
            ->sum("total_amount");

        // Payment by method
        $paymentByMethod = (clone $query)
            ->where("payment_status", "paid")
            ->selectRaw(
                "payment_method, COUNT(*) as count, SUM(total_amount) as total",
            )
            ->groupBy("payment_method")
            ->get();

        return response()->json([
            "success" => true,
            "data" => [
                "total" => $total,
                "unpaid" => $unpaid,
                "paid" => $paid,
                "total_revenue" => $totalRevenue,
                "total_unpaid" => $totalUnpaid,
                "payment_by_method" => $paymentByMethod,
            ],
        ]);
    }

    /**
     * Store a newly created invoice
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "medical_record_id" => ["required", "exists:medical_records,id"],
            "items" => ["nullable", "array"],
            "items.*.item_type" => ["nullable", "in:service,medicine,other"],
            "items.*.item_name" => ["required_with:items", "string"],
            "items.*.quantity" => ["nullable", "integer", "min:1"],
            "items.*.unit_price" => ["nullable", "numeric", "min:0"],
            "items.*.notes" => ["nullable", "string"],
            "discount_percent" => ["nullable", "numeric", "min:0", "max:100"],
            "discount_amount" => ["nullable", "numeric", "min:0"],
            "notes" => ["nullable", "string"],
        ]);

        $medicalRecord = MedicalRecord::findOrFail(
            $validated["medical_record_id"],
        );

        // Check if invoice already exists
        $existing = Invoice::where(
            "medical_record_id",
            $medicalRecord->id,
        )->first();
        if ($existing) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Invoice sudah ada untuk rekam medis ini",
                    "data" => $existing->load(["patient", "items"]),
                ],
                422,
            );
        }

        DB::beginTransaction();
        try {
            $invoice = Invoice::create([
                "invoice_number" => Invoice::generateInvoiceNumber(),
                "medical_record_id" => $medicalRecord->id,
                "patient_id" => $medicalRecord->patient_id,
                "discount_percent" => $validated["discount_percent"] ?? 0,
                "discount_amount" => $validated["discount_amount"] ?? 0,
                "notes" => $validated["notes"] ?? null,
                "payment_status" => "unpaid",
            ]);

            $subtotal = 0;

            // Add items from request
            if (isset($validated["items"])) {
                foreach ($validated["items"] as $item) {
                    $quantity = $item["quantity"] ?? 1;
                    $unitPrice = $item["unit_price"] ?? 0;
                    $totalPrice = $quantity * $unitPrice;

                    InvoiceItem::create([
                        "invoice_id" => $invoice->id,
                        "item_type" => $item["item_type"] ?? "service",
                        "item_name" => $item["item_name"],
                        "quantity" => $quantity,
                        "unit_price" => $unitPrice,
                        "total_price" => $totalPrice,
                        "notes" => $item["notes"] ?? null,
                    ]);

                    $subtotal += $totalPrice;
                }
            }

            // Also add services from medical record
            foreach ($medicalRecord->services as $service) {
                InvoiceItem::create([
                    "invoice_id" => $invoice->id,
                    "item_type" => "service",
                    "item_name" => $service->service_name,
                    "quantity" => $service->quantity,
                    "unit_price" => $service->unit_price,
                    "total_price" => $service->total_price,
                ]);
                $subtotal += $service->total_price;
            }

            // Calculate totals
            $discountAmount = $validated["discount_amount"] ?? 0;
            if (($validated["discount_percent"] ?? 0) > 0) {
                $discountAmount =
                    $subtotal * ($validated["discount_percent"] / 100);
            }

            $totalAmount = $subtotal - $discountAmount;

            $invoice->update([
                "subtotal" => $subtotal,
                "discount_amount" => $discountAmount,
                "total_amount" => $totalAmount,
            ]);

            DB::commit();

            $invoice->load(["patient", "medicalRecord.department", "items"]);

            return response()->json(
                [
                    "success" => true,
                    "message" => "Invoice berhasil dibuat",
                    "data" => $invoice,
                ],
                201,
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    "success" => false,
                    "message" => "Gagal membuat invoice: " . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Display the specified invoice
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load([
            "patient",
            "medicalRecord.department",
            "medicalRecord.doctor",
            "cashier",
            "items",
        ]);

        return response()->json([
            "success" => true,
            "data" => $invoice,
        ]);
    }

    /**
     * Process payment
     */
    public function pay(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->payment_status === "paid") {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Invoice sudah lunas",
                ],
                422,
            );
        }

        $validated = $request->validate([
            "paid_amount" => ["required", "numeric", "min:0"],
            "payment_method" => [
                "required",
                "in:cash,card,transfer,bpjs,insurance",
            ],
            "notes" => ["nullable", "string"],
        ]);

        if ($validated["paid_amount"] < $invoice->total_amount) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Jumlah pembayaran kurang dari total tagihan",
                ],
                422,
            );
        }

        $changeAmount = $validated["paid_amount"] - $invoice->total_amount;

        $invoice->update([
            "paid_amount" => $validated["paid_amount"],
            "change_amount" => $changeAmount,
            "payment_method" => $validated["payment_method"],
            "payment_status" => "paid",
            "payment_date" => now(),
            "cashier_id" => auth()->id(),
            "notes" => $validated["notes"] ?? $invoice->notes,
        ]);

        $invoice->load([
            "patient",
            "medicalRecord.department",
            "cashier",
            "items",
        ]);

        return response()->json([
            "success" => true,
            "message" => "Pembayaran berhasil",
            "data" => $invoice,
        ]);
    }

    /**
     * Get invoice for printing
     */
    public function print(Invoice $invoice): JsonResponse
    {
        $invoice->load([
            "patient",
            "medicalRecord.department",
            "medicalRecord.doctor",
            "medicalRecord.diagnoses",
            "cashier",
            "items",
        ]);

        // Get clinic settings for header
        $clinicSettings = \App\Models\ClinicSetting::first();

        return response()->json([
            "success" => true,
            "data" => [
                "invoice" => $invoice,
                "clinic" => $clinicSettings,
            ],
        ]);
    }

    /**
     * Update the specified invoice
     */
    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->payment_status === "paid") {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Invoice yang sudah dibayar tidak dapat diubah",
                ],
                422,
            );
        }

        $validated = $request->validate([
            "items" => ["nullable", "array"],
            "items.*.item_type" => ["nullable", "in:service,medicine,other"],
            "items.*.item_name" => ["required_with:items", "string"],
            "items.*.quantity" => ["nullable", "integer", "min:1"],
            "items.*.unit_price" => ["nullable", "numeric", "min:0"],
            "items.*.notes" => ["nullable", "string"],
            "discount_percent" => ["nullable", "numeric", "min:0", "max:100"],
            "discount_amount" => ["nullable", "numeric", "min:0"],
            "notes" => ["nullable", "string"],
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated["items"])) {
                $invoice->items()->delete();

                $subtotal = 0;
                foreach ($validated["items"] as $item) {
                    $quantity = $item["quantity"] ?? 1;
                    $unitPrice = $item["unit_price"] ?? 0;
                    $totalPrice = $quantity * $unitPrice;

                    InvoiceItem::create([
                        "invoice_id" => $invoice->id,
                        "item_type" => $item["item_type"] ?? "service",
                        "item_name" => $item["item_name"],
                        "quantity" => $quantity,
                        "unit_price" => $unitPrice,
                        "total_price" => $totalPrice,
                        "notes" => $item["notes"] ?? null,
                    ]);

                    $subtotal += $totalPrice;
                }

                $discountAmount =
                    $validated["discount_amount"] ?? $invoice->discount_amount;
                if (
                    isset($validated["discount_percent"]) &&
                    $validated["discount_percent"] > 0
                ) {
                    $discountAmount =
                        $subtotal * ($validated["discount_percent"] / 100);
                }

                $invoice->update([
                    "subtotal" => $subtotal,
                    "discount_percent" =>
                        $validated["discount_percent"] ??
                        $invoice->discount_percent,
                    "discount_amount" => $discountAmount,
                    "total_amount" => $subtotal - $discountAmount,
                    "notes" => $validated["notes"] ?? $invoice->notes,
                ]);
            } else {
                $invoice->update([
                    "discount_percent" =>
                        $validated["discount_percent"] ??
                        $invoice->discount_percent,
                    "discount_amount" =>
                        $validated["discount_amount"] ??
                        $invoice->discount_amount,
                    "notes" => $validated["notes"] ?? $invoice->notes,
                ]);
            }

            DB::commit();

            $invoice->load(["patient", "items"]);

            return response()->json([
                "success" => true,
                "message" => "Invoice berhasil diperbarui",
                "data" => $invoice,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Gagal memperbarui invoice: " . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Remove the specified invoice (cancel)
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        if ($invoice->payment_status === "paid") {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Invoice yang sudah dibayar tidak dapat dihapus",
                ],
                422,
            );
        }

        $invoice->delete();

        return response()->json([
            "success" => true,
            "message" => "Invoice berhasil dihapus",
        ]);
    }
}
