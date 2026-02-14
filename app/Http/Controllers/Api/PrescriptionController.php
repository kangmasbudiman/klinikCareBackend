<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    /**
     * Display a listing of prescriptions
     */
    public function index(Request $request): JsonResponse
    {
        $query = Prescription::with([
            "medicalRecord.patient",
            "medicalRecord.department",
            "items",
        ]);

        // Date filter
        if ($request->has("date")) {
            $query->whereDate("created_at", $request->date);
        }

        // Status filter
        if ($request->has("status") && $request->status) {
            $query->where("status", $request->status);
        }

        // Search
        if ($request->has("search") && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where(
                    "prescription_number",
                    "like",
                    "%{$search}%",
                )->orWhereHas("medicalRecord.patient", function ($q2) use (
                    $search,
                ) {
                    $q2->where("name", "like", "%{$search}%");
                });
            });
        }

        $query->orderBy("created_at", "desc");

        $perPage = $request->get("per_page", 20);
        $prescriptions = $query->paginate($perPage);

        return response()->json([
            "success" => true,
            "data" => $prescriptions->items(),
            "meta" => [
                "current_page" => $prescriptions->currentPage(),
                "last_page" => $prescriptions->lastPage(),
                "per_page" => $prescriptions->perPage(),
                "total" => $prescriptions->total(),
            ],
        ]);
    }

    /**
     * Store a newly created prescription
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "medical_record_id" => ["required", "exists:medical_records,id"],
            "notes" => ["nullable", "string"],
            "items" => ["required", "array", "min:1"],
            "items.*.medicine_name" => ["required", "string"],
            "items.*.dosage" => ["nullable", "string", "max:100"],
            "items.*.frequency" => ["nullable", "string", "max:100"],
            "items.*.duration" => ["nullable", "string", "max:100"],
            "items.*.quantity" => ["nullable", "integer", "min:1"],
            "items.*.instructions" => ["nullable", "string"],
            "items.*.notes" => ["nullable", "string"],
        ]);

        DB::beginTransaction();
        try {
            $prescription = Prescription::create([
                "medical_record_id" => $validated["medical_record_id"],
                "prescription_number" => Prescription::generatePrescriptionNumber(),
                "notes" => $validated["notes"] ?? null,
                "status" => "pending",
            ]);

            foreach ($validated["items"] as $item) {
                PrescriptionItem::create([
                    "prescription_id" => $prescription->id,
                    "medicine_name" => $item["medicine_name"],
                    "dosage" => $item["dosage"] ?? null,
                    "frequency" => $item["frequency"] ?? null,
                    "duration" => $item["duration"] ?? null,
                    "quantity" => $item["quantity"] ?? 1,
                    "instructions" => $item["instructions"] ?? null,
                    "notes" => $item["notes"] ?? null,
                ]);
            }

            DB::commit();

            $prescription->load(["medicalRecord.patient", "items"]);

            return response()->json(
                [
                    "success" => true,
                    "message" => "Resep berhasil dibuat",
                    "data" => $prescription,
                ],
                201,
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    "success" => false,
                    "message" => "Gagal membuat resep: " . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Display the specified prescription
     */
    public function show(Prescription $prescription): JsonResponse
    {
        $prescription->load([
            "medicalRecord.patient",
            "medicalRecord.department",
            "medicalRecord.doctor",
            "items",
        ]);

        return response()->json([
            "success" => true,
            "data" => $prescription,
        ]);
    }

    /**
     * Update the specified prescription
     */
    public function update(
        Request $request,
        Prescription $prescription,
    ): JsonResponse {
        if ($prescription->status === "completed") {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Resep yang sudah selesai tidak dapat diubah",
                ],
                422,
            );
        }

        $validated = $request->validate([
            "notes" => ["nullable", "string"],
            "items" => ["nullable", "array"],
            "items.*.medicine_name" => ["required_with:items", "string"],
            "items.*.dosage" => ["nullable", "string", "max:100"],
            "items.*.frequency" => ["nullable", "string", "max:100"],
            "items.*.duration" => ["nullable", "string", "max:100"],
            "items.*.quantity" => ["nullable", "integer", "min:1"],
            "items.*.instructions" => ["nullable", "string"],
            "items.*.notes" => ["nullable", "string"],
        ]);

        DB::beginTransaction();
        try {
            $prescription->update([
                "notes" => $validated["notes"] ?? $prescription->notes,
            ]);

            if (isset($validated["items"])) {
                $prescription->items()->delete();

                foreach ($validated["items"] as $item) {
                    PrescriptionItem::create([
                        "prescription_id" => $prescription->id,
                        "medicine_name" => $item["medicine_name"],
                        "dosage" => $item["dosage"] ?? null,
                        "frequency" => $item["frequency"] ?? null,
                        "duration" => $item["duration"] ?? null,
                        "quantity" => $item["quantity"] ?? 1,
                        "instructions" => $item["instructions"] ?? null,
                        "notes" => $item["notes"] ?? null,
                    ]);
                }
            }

            DB::commit();

            $prescription->load("items");

            return response()->json([
                "success" => true,
                "message" => "Resep berhasil diperbarui",
                "data" => $prescription,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    "success" => false,
                    "message" => "Gagal memperbarui resep: " . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Update prescription status
     */
    public function updateStatus(
        Request $request,
        Prescription $prescription,
    ): JsonResponse {
        $validated = $request->validate([
            "status" => [
                "required",
                "in:pending,processed,completed,cancelled",
            ],
        ]);

        $prescription->update([
            "status" => $validated["status"],
        ]);

        return response()->json([
            "success" => true,
            "message" => "Status resep berhasil diperbarui",
            "data" => $prescription,
        ]);
    }

    /**
     * Remove the specified prescription
     */
    public function destroy(Prescription $prescription): JsonResponse
    {
        if ($prescription->status === "completed") {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Resep yang sudah selesai tidak dapat dihapus",
                ],
                422,
            );
        }

        $prescription->update(["status" => "cancelled"]);

        return response()->json([
            "success" => true,
            "message" => "Resep dibatalkan",
        ]);
    }
}
