<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordDiagnosis;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get report summary stats
     */
    public function summary(Request $request): JsonResponse
    {
        $startDate = $request->get(
            "start_date",
            now()->startOfMonth()->toDateString(),
        );
        $endDate = $request->get(
            "end_date",
            now()->endOfMonth()->toDateString(),
        );

        // Previous period for comparison
        $periodDays =
            Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $prevStartDate = Carbon::parse($startDate)
            ->subDays($periodDays)
            ->toDateString();
        $prevEndDate = Carbon::parse($startDate)->subDay()->toDateString();

        // Current period stats
        $totalVisits = MedicalRecord::where("status", "completed")
            ->whereBetween("visit_date", [$startDate, $endDate])
            ->when(
                $request->department_id,
                fn($q) => $q->where("department_id", $request->department_id),
            )
            ->when(
                $request->doctor_id,
                fn($q) => $q->where("doctor_id", $request->doctor_id),
            )
            ->count();

        $prevVisits = MedicalRecord::where("status", "completed")
            ->whereBetween("visit_date", [$prevStartDate, $prevEndDate])
            ->when(
                $request->department_id,
                fn($q) => $q->where("department_id", $request->department_id),
            )
            ->when(
                $request->doctor_id,
                fn($q) => $q->where("doctor_id", $request->doctor_id),
            )
            ->count();

        $totalRevenue = Invoice::where("payment_status", "paid")
            ->whereBetween(DB::raw("DATE(payment_date)"), [
                $startDate,
                $endDate,
            ])
            ->when(
                $request->department_id,
                fn($q) => $q->whereHas(
                    "medicalRecord",
                    fn($q2) => $q2->where(
                        "department_id",
                        $request->department_id,
                    ),
                ),
            )
            ->sum("total_amount");

        $prevRevenue = Invoice::where("payment_status", "paid")
            ->whereBetween(DB::raw("DATE(payment_date)"), [
                $prevStartDate,
                $prevEndDate,
            ])
            ->when(
                $request->department_id,
                fn($q) => $q->whereHas(
                    "medicalRecord",
                    fn($q2) => $q2->where(
                        "department_id",
                        $request->department_id,
                    ),
                ),
            )
            ->sum("total_amount");

        $newPatients = Patient::whereBetween(DB::raw("DATE(created_at)"), [
            $startDate,
            $endDate,
        ])->count();
        $prevNewPatients = Patient::whereBetween(DB::raw("DATE(created_at)"), [
            $prevStartDate,
            $prevEndDate,
        ])->count();

        $uniquePatients = MedicalRecord::where("status", "completed")
            ->whereBetween("visit_date", [$startDate, $endDate])
            ->distinct("patient_id")
            ->count("patient_id");

        $avgRevenuePerVisit =
            $totalVisits > 0 ? round($totalRevenue / $totalVisits) : 0;

        $visitsChange =
            $prevVisits > 0
                ? round((($totalVisits - $prevVisits) / $prevVisits) * 100, 1)
                : 0;
        $revenueChange =
            $prevRevenue > 0
                ? round(
                    (($totalRevenue - $prevRevenue) / $prevRevenue) * 100,
                    1,
                )
                : 0;
        $newPatientsChange =
            $prevNewPatients > 0
                ? round(
                    (($newPatients - $prevNewPatients) / $prevNewPatients) *
                        100,
                    1,
                )
                : 0;

        return response()->json([
            "success" => true,
            "data" => [
                "total_visits" => $totalVisits,
                "total_revenue" => (float) $totalRevenue,
                "total_new_patients" => $newPatients,
                "total_unique_patients" => $uniquePatients,
                "avg_revenue_per_visit" => $avgRevenuePerVisit,
                "visits_change_percent" => $visitsChange,
                "revenue_change_percent" => $revenueChange,
                "new_patients_change_percent" => $newPatientsChange,
            ],
        ]);
    }

    /**
     * Get visit report data
     */
    public function visits(Request $request): JsonResponse
    {
        $startDate = $request->get(
            "start_date",
            now()->startOfMonth()->toDateString(),
        );
        $endDate = $request->get(
            "end_date",
            now()->endOfMonth()->toDateString(),
        );
        $groupBy = $request->get("group_by", "daily");

        $baseQuery = MedicalRecord::where("status", "completed")
            ->whereBetween("visit_date", [$startDate, $endDate])
            ->when(
                $request->department_id,
                fn($q) => $q->where("department_id", $request->department_id),
            )
            ->when(
                $request->doctor_id,
                fn($q) => $q->where("doctor_id", $request->doctor_id),
            );

        // By date
        $dateFormat = $groupBy === "monthly" ? "%Y-%m" : "%Y-%m-%d";
        $byDate = (clone $baseQuery)
            ->select(
                DB::raw("DATE_FORMAT(visit_date, '{$dateFormat}') as date"),
                DB::raw("COUNT(*) as total"),
                DB::raw("COUNT(DISTINCT patient_id) as unique_patients"),
            )
            ->groupBy("date")
            ->orderBy("date")
            ->get();

        // Count new patients per date (patients whose first visit falls in the period)
        $newPatientsByDate = MedicalRecord::where("status", "completed")
            ->whereBetween("visit_date", [$startDate, $endDate])
            ->when(
                $request->department_id,
                fn($q) => $q->where("department_id", $request->department_id),
            )
            ->when(
                $request->doctor_id,
                fn($q) => $q->where("doctor_id", $request->doctor_id),
            )
            ->whereIn("patient_id", function ($query) use (
                $startDate,
                $endDate,
            ) {
                $query
                    ->select("patient_id")
                    ->from("medical_records")
                    ->where("status", "completed")
                    ->groupBy("patient_id")
                    ->havingRaw("MIN(visit_date) BETWEEN ? AND ?", [
                        $startDate,
                        $endDate,
                    ]);
            })
            ->select(
                DB::raw("DATE_FORMAT(visit_date, '%Y-%m-%d') as date"),
                DB::raw("COUNT(DISTINCT patient_id) as new_patients"),
            )
            ->groupBy("date")
            ->pluck("new_patients", "date");

        $byDateWithNew = $byDate->map(function ($item) use (
            $newPatientsByDate,
        ) {
            $newCount = $newPatientsByDate[$item->date] ?? 0;
            return [
                "date" => $item->date,
                "total" => $item->total,
                "new_patients" => $newCount,
                "returning_patients" => $item->total - $newCount,
            ];
        });

        // By department
        $byDepartment = (clone $baseQuery)
            ->select("department_id", DB::raw("COUNT(*) as total"))
            ->groupBy("department_id")
            ->with("department:id,name")
            ->orderByDesc("total")
            ->get()
            ->map(
                fn($item) => [
                    "department_id" => $item->department_id,
                    "department_name" => $item->department?->name ?? "-",
                    "total" => $item->total,
                ],
            );

        // By doctor
        $byDoctor = (clone $baseQuery)
            ->select("doctor_id", DB::raw("COUNT(*) as total"))
            ->groupBy("doctor_id")
            ->with("doctor:id,name")
            ->orderByDesc("total")
            ->get()
            ->map(
                fn($item) => [
                    "doctor_id" => $item->doctor_id,
                    "doctor_name" => $item->doctor?->name ?? "-",
                    "total" => $item->total,
                ],
            );

        // Totals
        $totalVisits = (clone $baseQuery)->count();
        $totalNewPatients = $newPatientsByDate->sum();

        return response()->json([
            "success" => true,
            "data" => [
                "by_date" => $byDateWithNew->values(),
                "by_department" => $byDepartment->values(),
                "by_doctor" => $byDoctor->values(),
                "totals" => [
                    "total_visits" => $totalVisits,
                    "new_patients" => $totalNewPatients,
                    "returning_patients" => $totalVisits - $totalNewPatients,
                ],
            ],
        ]);
    }

    /**
     * Get revenue report data
     */
    public function revenue(Request $request): JsonResponse
    {
        $startDate = $request->get(
            "start_date",
            now()->startOfMonth()->toDateString(),
        );
        $endDate = $request->get(
            "end_date",
            now()->endOfMonth()->toDateString(),
        );
        $groupBy = $request->get("group_by", "daily");

        $baseQuery = Invoice::whereBetween(
            DB::raw("DATE(invoices.created_at)"),
            [$startDate, $endDate],
        )->when(
            $request->department_id,
            fn($q) => $q->whereHas(
                "medicalRecord",
                fn($q2) => $q2->where("department_id", $request->department_id),
            ),
        );

        // By date
        $dateFormat = $groupBy === "monthly" ? "%Y-%m" : "%Y-%m-%d";
        $byDate = (clone $baseQuery)
            ->select(
                DB::raw(
                    "DATE_FORMAT(invoices.created_at, '{$dateFormat}') as date",
                ),
                DB::raw("SUM(total_amount) as total_amount"),
                DB::raw(
                    "SUM(CASE WHEN payment_status = 'paid' THEN paid_amount ELSE 0 END) as paid_amount",
                ),
                DB::raw("COUNT(*) as count"),
            )
            ->groupBy("date")
            ->orderBy("date")
            ->get();

        // By payment method (only paid invoices)
        $byPaymentMethod = (clone $baseQuery)
            ->where("payment_status", "paid")
            ->select(
                "payment_method",
                DB::raw("SUM(total_amount) as total_amount"),
                DB::raw("COUNT(*) as count"),
            )
            ->groupBy("payment_method")
            ->orderByDesc("total_amount")
            ->get()
            ->map(
                fn($item) => [
                    "payment_method" => $item->payment_method,
                    "label" =>
                        Invoice::PAYMENT_METHOD_LABELS[$item->payment_method] ??
                        $item->payment_method,
                    "total_amount" => (float) $item->total_amount,
                    "count" => $item->count,
                ],
            );

        // By department
        $byDepartment = (clone $baseQuery)
            ->select(
                "medical_records.department_id",
                DB::raw("SUM(invoices.total_amount) as total_amount"),
                DB::raw("COUNT(invoices.id) as count"),
            )
            ->join(
                "medical_records",
                "invoices.medical_record_id",
                "=",
                "medical_records.id",
            )
            ->groupBy("medical_records.department_id")
            ->orderByDesc("total_amount")
            ->get()
            ->map(function ($item) {
                $dept = Department::find($item->department_id);
                return [
                    "department_id" => $item->department_id,
                    "department_name" => $dept?->name ?? "-",
                    "total_amount" => (float) $item->total_amount,
                    "count" => $item->count,
                ];
            });

        // Totals
        $totalAmount = (clone $baseQuery)->sum("total_amount");
        $paidAmount = (clone $baseQuery)
            ->where("payment_status", "paid")
            ->sum("total_amount");
        $unpaidAmount = (clone $baseQuery)
            ->where("payment_status", "unpaid")
            ->sum("total_amount");
        $invoiceCount = (clone $baseQuery)->count();

        return response()->json([
            "success" => true,
            "data" => [
                "by_date" => $byDate,
                "by_payment_method" => $byPaymentMethod->values(),
                "by_department" => $byDepartment->values(),
                "totals" => [
                    "total_amount" => (float) $totalAmount,
                    "paid_amount" => (float) $paidAmount,
                    "unpaid_amount" => (float) $unpaidAmount,
                    "invoice_count" => $invoiceCount,
                ],
            ],
        ]);
    }

    /**
     * Get top diagnoses report
     */
    public function diagnoses(Request $request): JsonResponse
    {
        $startDate = $request->get(
            "start_date",
            now()->startOfMonth()->toDateString(),
        );
        $endDate = $request->get(
            "end_date",
            now()->endOfMonth()->toDateString(),
        );
        $limit = $request->get("limit", 20);

        $query = MedicalRecordDiagnosis::whereHas("medicalRecord", function (
            $q,
        ) use ($startDate, $endDate, $request) {
            $q->where("status", "completed")
                ->whereBetween("visit_date", [$startDate, $endDate])
                ->when(
                    $request->department_id,
                    fn($q2) => $q2->where(
                        "department_id",
                        $request->department_id,
                    ),
                );
        });

        $totalDiagnoses = (clone $query)->count();

        $diagnoses = (clone $query)
            ->select("icd_code", "icd_name", DB::raw("COUNT(*) as count"))
            ->whereNotNull("icd_code")
            ->groupBy("icd_code", "icd_name")
            ->orderByDesc("count")
            ->limit($limit)
            ->get()
            ->map(
                fn($item) => [
                    "icd_code" => $item->icd_code,
                    "icd_name" => $item->icd_name,
                    "count" => $item->count,
                    "percentage" =>
                        $totalDiagnoses > 0
                            ? round(($item->count / $totalDiagnoses) * 100, 1)
                            : 0,
                ],
            );

        $uniqueCodes = (clone $query)
            ->whereNotNull("icd_code")
            ->distinct("icd_code")
            ->count("icd_code");

        return response()->json([
            "success" => true,
            "data" => $diagnoses->values(),
            "totals" => [
                "total_diagnoses" => $totalDiagnoses,
                "unique_codes" => $uniqueCodes,
            ],
        ]);
    }

    /**
     * Get doctor performance report
     */
    public function doctors(Request $request): JsonResponse
    {
        $startDate = $request->get(
            "start_date",
            now()->startOfMonth()->toDateString(),
        );
        $endDate = $request->get(
            "end_date",
            now()->endOfMonth()->toDateString(),
        );

        $doctors = MedicalRecord::whereBetween("visit_date", [
            $startDate,
            $endDate,
        ])
            ->when(
                $request->department_id,
                fn($q) => $q->where("department_id", $request->department_id),
            )
            ->select(
                "doctor_id",
                "department_id",
                DB::raw(
                    "SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_records",
                ),
                DB::raw(
                    "SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_records",
                ),
                DB::raw("COUNT(*) as total_patients"),
            )
            ->groupBy("doctor_id", "department_id")
            ->with(["doctor:id,name", "department:id,name"])
            ->orderByDesc("total_patients")
            ->get()
            ->map(function ($item) use ($startDate, $endDate) {
                $revenue = Invoice::where("payment_status", "paid")
                    ->whereHas(
                        "medicalRecord",
                        fn($q) => $q
                            ->where("doctor_id", $item->doctor_id)
                            ->whereBetween("visit_date", [
                                $startDate,
                                $endDate,
                            ]),
                    )
                    ->sum("total_amount");

                return [
                    "doctor_id" => $item->doctor_id,
                    "doctor_name" => $item->doctor?->name ?? "-",
                    "department_name" => $item->department?->name ?? "-",
                    "total_patients" => $item->total_patients,
                    "total_revenue" => (float) $revenue,
                    "avg_revenue_per_visit" =>
                        $item->completed_records > 0
                            ? round($revenue / $item->completed_records)
                            : 0,
                    "completed_records" => $item->completed_records,
                    "cancelled_records" => $item->cancelled_records,
                ];
            });

        return response()->json([
            "success" => true,
            "data" => $doctors->values(),
        ]);
    }

    /**
     * Get department report
     */
    public function departments(Request $request): JsonResponse
    {
        $startDate = $request->get(
            "start_date",
            now()->startOfMonth()->toDateString(),
        );
        $endDate = $request->get(
            "end_date",
            now()->endOfMonth()->toDateString(),
        );

        $departments = MedicalRecord::where("status", "completed")
            ->whereBetween("visit_date", [$startDate, $endDate])
            ->select(
                "department_id",
                DB::raw("COUNT(*) as total_visits"),
                DB::raw("COUNT(DISTINCT patient_id) as total_patients"),
            )
            ->groupBy("department_id")
            ->with("department:id,name,color")
            ->orderByDesc("total_visits")
            ->get()
            ->map(function ($item) use ($startDate, $endDate) {
                $revenue = Invoice::where("payment_status", "paid")
                    ->whereHas(
                        "medicalRecord",
                        fn($q) => $q
                            ->where("department_id", $item->department_id)
                            ->whereBetween("visit_date", [
                                $startDate,
                                $endDate,
                            ]),
                    )
                    ->sum("total_amount");

                // Top diagnoses for this department
                $topDiagnoses = MedicalRecordDiagnosis::whereHas(
                    "medicalRecord",
                    function ($q) use ($item, $startDate, $endDate) {
                        $q->where("status", "completed")
                            ->where("department_id", $item->department_id)
                            ->whereBetween("visit_date", [
                                $startDate,
                                $endDate,
                            ]);
                    },
                )
                    ->select(
                        "icd_code",
                        "icd_name",
                        DB::raw("COUNT(*) as count"),
                    )
                    ->whereNotNull("icd_code")
                    ->groupBy("icd_code", "icd_name")
                    ->orderByDesc("count")
                    ->limit(5)
                    ->get();

                return [
                    "department_id" => $item->department_id,
                    "department_name" => $item->department?->name ?? "-",
                    "department_color" => $item->department?->color ?? "blue",
                    "total_visits" => $item->total_visits,
                    "total_revenue" => (float) $revenue,
                    "total_patients" => $item->total_patients,
                    "avg_revenue_per_visit" =>
                        $item->total_visits > 0
                            ? round($revenue / $item->total_visits)
                            : 0,
                    "top_diagnoses" => $topDiagnoses,
                ];
            });

        return response()->json([
            "success" => true,
            "data" => $departments->values(),
        ]);
    }
}
