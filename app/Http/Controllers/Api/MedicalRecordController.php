<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordDiagnosis;
use App\Models\MedicalRecordService;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MedicalRecordController extends Controller
{
    /**
     * Display a listing of medical records
     */
    public function index(Request $request): JsonResponse
    {
        $query = MedicalRecord::with([
            "patient",
            "department",
            "doctor",
            "queue",
        ]);

        // Date filter
        if ($request->has("date")) {
            $query->whereDate("visit_date", $request->date);
        }

        // Department filter
        if ($request->has("department_id") && $request->department_id) {
            $query->where("department_id", $request->department_id);
        }

        // Doctor filter
        if ($request->has("doctor_id") && $request->doctor_id) {
            $query->where("doctor_id", $request->doctor_id);
        }

        // Status filter
        if ($request->has("status") && $request->status) {
            $query->where("status", $request->status);
        }

        // Search
        if ($request->has("search") && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where("record_number", "like", "%{$search}%")->orWhereHas(
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
        $records = $query->paginate($perPage);

        return response()->json([
            "success" => true,
            "data" => $records->items(),
            "meta" => [
                "current_page" => $records->currentPage(),
                "last_page" => $records->lastPage(),
                "per_page" => $records->perPage(),
                "total" => $records->total(),
            ],
        ]);
    }

    /**
     * Get patients waiting for examination (per department)
     */
    public function pending(Request $request): JsonResponse
    {
        $departmentId = $request->get("department_id");

        // Get queues that are in_service but don't have medical record yet
        $query = Queue::with(["patient", "department"])
            ->today()
            ->where("status", "in_service")
            ->whereDoesntHave("medicalRecord");

        if ($departmentId) {
            $query->where("department_id", $departmentId);
        }

        $waiting = $query->orderBy("started_at", "asc")->get();

        // Also get medical records that are in_progress
        $inProgressQuery = MedicalRecord::with([
            "patient",
            "department",
            "doctor",
            "queue",
        ])
            ->today()
            ->where("status", "in_progress");

        if ($departmentId) {
            $inProgressQuery->where("department_id", $departmentId);
        }

        $inProgress = $inProgressQuery->orderBy("created_at", "asc")->get();

        return response()->json([
            "success" => true,
            "data" => [
                "waiting" => $waiting,
                "in_progress" => $inProgress,
            ],
        ]);
    }

    /**
     * Get examination statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $date = $request->get("date", now()->toDateString());
        $departmentId = $request->get("department_id");
        $doctorId = $request->get("doctor_id");

        $query = MedicalRecord::whereDate("visit_date", $date);

        if ($departmentId) {
            $query->where("department_id", $departmentId);
        }

        if ($doctorId) {
            $query->where("doctor_id", $doctorId);
        }

        $total = (clone $query)->count();
        $inProgress = (clone $query)->where("status", "in_progress")->count();
        $completed = (clone $query)->where("status", "completed")->count();
        $cancelled = (clone $query)->where("status", "cancelled")->count();

        // Average examination time
        $avgExamTime = (clone $query)
            ->where("status", "completed")
            ->whereNotNull("completed_at")
            ->selectRaw(
                "AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) as avg_time",
            )
            ->value("avg_time");

        // Get waiting count from queues
        $waitingQuery = Queue::today()->where("status", "in_service");
        if ($departmentId) {
            $waitingQuery->where("department_id", $departmentId);
        }
        $waiting = $waitingQuery->count();

        return response()->json([
            "success" => true,
            "data" => [
                "total" => $total,
                "waiting" => $waiting,
                "in_progress" => $inProgress,
                "completed" => $completed,
                "cancelled" => $cancelled,
                "avg_exam_time" => round($avgExamTime ?? 0),
            ],
        ]);
    }

    /**
     * Store a newly created medical record (start examination)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "queue_id" => ["required", "exists:queues,id"],
        ]);

        $queue = Queue::with([
            "patient",
            "department.defaultService",
        ])->findOrFail($validated["queue_id"]);

        if (!$queue->patient_id) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Pasien belum terdaftar pada antrian ini",
                ],
                422,
            );
        }

        // Check if medical record already exists for this queue
        $existing = MedicalRecord::where("queue_id", $queue->id)->first();
        if ($existing) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Rekam medis sudah ada untuk antrian ini",
                    "data" => $existing->load([
                        "patient",
                        "department",
                        "doctor",
                    ]),
                ],
                422,
            );
        }

        DB::beginTransaction();
        try {
            $medicalRecord = MedicalRecord::create([
                "record_number" => MedicalRecord::generateRecordNumber(),
                "queue_id" => $queue->id,
                "patient_id" => $queue->patient_id,
                "department_id" => $queue->department_id,
                "doctor_id" => auth()->id(),
                "visit_date" => now()->toDateString(),
                "status" => "in_progress",
            ]);

            // Auto-add default service (konsultasi) if department has one
            if ($queue->department && $queue->department->defaultService) {
                $defaultService = $queue->department->defaultService;
                $unitPrice =
                    $defaultService->total_price ??
                    $defaultService->base_price +
                        $defaultService->doctor_fee +
                        $defaultService->hospital_fee;

                MedicalRecordService::create([
                    "medical_record_id" => $medicalRecord->id,
                    "service_id" => $defaultService->id,
                    "service_name" => $defaultService->name,
                    "quantity" => 1,
                    "unit_price" => $unitPrice,
                    "total_price" => $unitPrice,
                    "notes" => "Layanan konsultasi otomatis",
                ]);
            }

            DB::commit();

            $medicalRecord->load([
                "patient",
                "department",
                "doctor",
                "queue",
                "services.service",
            ]);

            return response()->json(
                [
                    "success" => true,
                    "message" => "Pemeriksaan dimulai",
                    "data" => $medicalRecord,
                ],
                201,
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Gagal memulai pemeriksaan: " . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Display the specified medical record
     */
    public function show(MedicalRecord $medicalRecord): JsonResponse
    {
        $medicalRecord->load([
            "patient",
            "department",
            "doctor",
            "queue",
            "diagnoses",
            "services.service",
            "prescriptions.items",
            "invoice.items",
        ]);

        return response()->json([
            "success" => true,
            "data" => $medicalRecord,
        ]);
    }

    /**
     * Update the specified medical record
     */
    public function update(
        Request $request,
        MedicalRecord $medicalRecord,
    ): JsonResponse {
        if ($medicalRecord->status === "completed") {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Rekam medis yang sudah selesai tidak dapat diubah",
                ],
                422,
            );
        }

        $validated = $request->validate([
            // Anamnesis
            "chief_complaint" => ["nullable", "string"],
            "present_illness" => ["nullable", "string"],
            "past_medical_history" => ["nullable", "string"],
            "family_history" => ["nullable", "string"],
            "allergy_notes" => ["nullable", "string"],
            // Vital Signs
            "blood_pressure_systolic" => [
                "nullable",
                "integer",
                "min:0",
                "max:300",
            ],
            "blood_pressure_diastolic" => [
                "nullable",
                "integer",
                "min:0",
                "max:200",
            ],
            "heart_rate" => ["nullable", "integer", "min:0", "max:300"],
            "respiratory_rate" => ["nullable", "integer", "min:0", "max:100"],
            "temperature" => ["nullable", "numeric", "min:30", "max:45"],
            "weight" => ["nullable", "numeric", "min:0", "max:500"],
            "height" => ["nullable", "numeric", "min:0", "max:300"],
            "oxygen_saturation" => ["nullable", "integer", "min:0", "max:100"],
            "physical_examination" => ["nullable", "string"],
            // Diagnosis
            "diagnosis" => ["nullable", "string"],
            "diagnosis_notes" => ["nullable", "string"],
            // Treatment
            "treatment" => ["nullable", "string"],
            "treatment_notes" => ["nullable", "string"],
            // Recommendations
            "recommendations" => ["nullable", "string"],
            "follow_up_date" => ["nullable", "date"],
            // CPPT / SOAP
            "soap_subjective" => ["nullable", "string"],
            "soap_objective" => ["nullable", "string"],
            "soap_assessment" => ["nullable", "string"],
            "soap_plan" => ["nullable", "string"],
            // Diagnoses (ICD)
            "diagnoses" => ["nullable", "array"],
            "diagnoses.*.icd_code" => ["nullable", "string", "max:20"],
            "diagnoses.*.icd_name" => ["nullable", "string"],
            "diagnoses.*.diagnosis_type" => [
                "nullable",
                "in:primary,secondary",
            ],
            "diagnoses.*.notes" => ["nullable", "string"],
            // Services
            "services" => ["nullable", "array"],
            "services.*.service_id" => ["nullable", "exists:services,id"],
            "services.*.service_name" => ["required_with:services", "string"],
            "services.*.quantity" => ["nullable", "integer", "min:1"],
            "services.*.unit_price" => ["nullable", "numeric", "min:0"],
            "services.*.notes" => ["nullable", "string"],
        ]);

        DB::beginTransaction();
        try {
            // Update main record
            $medicalRecord->update($validated);

            // Update diagnoses if provided
            if (isset($validated["diagnoses"])) {
                $medicalRecord->diagnoses()->delete();
                foreach ($validated["diagnoses"] as $diagnosis) {
                    MedicalRecordDiagnosis::create([
                        "medical_record_id" => $medicalRecord->id,
                        "icd_code" => $diagnosis["icd_code"] ?? null,
                        "icd_name" => $diagnosis["icd_name"] ?? null,
                        "diagnosis_type" =>
                            $diagnosis["diagnosis_type"] ?? "primary",
                        "notes" => $diagnosis["notes"] ?? null,
                    ]);
                }
            }

            // Update services if provided
            if (isset($validated["services"])) {
                $medicalRecord->services()->delete();
                foreach ($validated["services"] as $service) {
                    MedicalRecordService::create([
                        "medical_record_id" => $medicalRecord->id,
                        "service_id" => $service["service_id"] ?? null,
                        "service_name" => $service["service_name"],
                        "quantity" => $service["quantity"] ?? 1,
                        "unit_price" => $service["unit_price"] ?? 0,
                        "total_price" =>
                            ($service["quantity"] ?? 1) *
                            ($service["unit_price"] ?? 0),
                        "notes" => $service["notes"] ?? null,
                    ]);
                }
            }

            DB::commit();

            $medicalRecord->load([
                "patient",
                "department",
                "doctor",
                "diagnoses",
                "services.service",
            ]);

            return response()->json([
                "success" => true,
                "message" => "Rekam medis berhasil diperbarui",
                "data" => $medicalRecord,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Gagal memperbarui rekam medis: " . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Complete the examination and create invoice
     */
    public function complete(
        Request $request,
        MedicalRecord $medicalRecord,
    ): JsonResponse {
        if ($medicalRecord->status === "completed") {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Pemeriksaan sudah selesai",
                ],
                422,
            );
        }

        $validated = $request->validate([
            "create_invoice" => ["nullable", "boolean"],
        ]);

        DB::beginTransaction();
        try {
            // Complete medical record
            $medicalRecord->update([
                "status" => "completed",
                "completed_at" => now(),
            ]);

            // Complete queue
            $medicalRecord->queue()->update([
                "status" => "completed",
                "completed_at" => now(),
            ]);

            // Create invoice if requested
            if ($validated["create_invoice"] ?? true) {
                $this->createInvoiceFromMedicalRecord($medicalRecord);
            }

            DB::commit();

            $medicalRecord->load([
                "patient",
                "department",
                "doctor",
                "invoice",
            ]);

            return response()->json([
                "success" => true,
                "message" => "Pemeriksaan selesai",
                "data" => $medicalRecord,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Gagal menyelesaikan pemeriksaan: " . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Create invoice from medical record
     */
    private function createInvoiceFromMedicalRecord(
        MedicalRecord $medicalRecord,
    ): Invoice {
        $invoice = Invoice::create([
            "invoice_number" => Invoice::generateInvoiceNumber(),
            "medical_record_id" => $medicalRecord->id,
            "patient_id" => $medicalRecord->patient_id,
            "payment_status" => "unpaid",
        ]);

        $subtotal = 0;

        // Add services to invoice
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

        // Add medicines from prescriptions to invoice
        $prescriptions = $medicalRecord->prescriptions()->with("items")->get();
        foreach ($prescriptions as $prescription) {
            foreach ($prescription->items as $item) {
                // Try to find medicine price from medicines table
                $medicine = \App\Models\Medicine::where(
                    "name",
                    $item->medicine_name,
                )->first();
                $unitPrice = $medicine ? $medicine->selling_price : 0;
                $totalPrice = $unitPrice * $item->quantity;

                InvoiceItem::create([
                    "invoice_id" => $invoice->id,
                    "item_type" => "medicine",
                    "item_name" => $item->medicine_name,
                    "quantity" => $item->quantity,
                    "unit_price" => $unitPrice,
                    "total_price" => $totalPrice,
                    "notes" => $item->dosage . " - " . $item->frequency,
                ]);
                $subtotal += $totalPrice;
            }
        }

        // Update invoice totals
        $invoice->update([
            "subtotal" => $subtotal,
            "total_amount" => $subtotal,
        ]);

        return $invoice;
    }

    /**
     * Get completed examinations for today (for cashier to see)
     */
    public function completed(Request $request): JsonResponse
    {
        $date = $request->get("date", now()->toDateString());
        $departmentId = $request->get("department_id");

        $query = MedicalRecord::with([
            "patient",
            "department",
            "doctor",
            "queue",
            "invoice",
        ])
            ->whereDate("visit_date", $date)
            ->where("status", "completed")
            ->orderBy("completed_at", "desc");

        if ($departmentId) {
            $query->where("department_id", $departmentId);
        }

        // Filter for cashier: only show those without paid invoice
        if ($request->get("unpaid_only")) {
            $query->where(function ($q) {
                $q->whereDoesntHave("invoice")->orWhereHas("invoice", function (
                    $q2,
                ) {
                    $q2->where("payment_status", "!=", "paid");
                });
            });
        }

        $records = $query->get();

        return response()->json([
            "success" => true,
            "data" => $records,
        ]);
    }

    /**
     * Get patient medical history
     */
    public function patientHistory(Request $request, $patientId): JsonResponse
    {
        $query = MedicalRecord::with([
            "department",
            "doctor",
            "diagnoses",
            "prescriptions.items",
            "services",
        ])
            ->where("patient_id", $patientId)
            ->where("status", "completed")
            ->orderBy("visit_date", "desc");

        $perPage = $request->get("per_page", 10);
        $records = $query->paginate($perPage);

        return response()->json([
            "success" => true,
            "data" => $records->items(),
            "meta" => [
                "current_page" => $records->currentPage(),
                "last_page" => $records->lastPage(),
                "per_page" => $records->perPage(),
                "total" => $records->total(),
            ],
        ]);
    }

    /**
     * Add prescription to medical record
     */
    public function addPrescription(
        Request $request,
        MedicalRecord $medicalRecord,
    ): JsonResponse {
        $validated = $request->validate([
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
                "medical_record_id" => $medicalRecord->id,
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

            $prescription->load("items");

            return response()->json(
                [
                    "success" => true,
                    "message" => "Resep berhasil ditambahkan",
                    "data" => $prescription,
                ],
                201,
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    "success" => false,
                    "message" => "Gagal menambahkan resep: " . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Remove the specified medical record (cancel)
     */
    public function destroy(MedicalRecord $medicalRecord): JsonResponse
    {
        if ($medicalRecord->status === "completed") {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Rekam medis yang sudah selesai tidak dapat dihapus",
                ],
                422,
            );
        }

        $medicalRecord->update(["status" => "cancelled"]);

        return response()->json([
            "success" => true,
            "message" => "Pemeriksaan dibatalkan",
        ]);
    }
}
