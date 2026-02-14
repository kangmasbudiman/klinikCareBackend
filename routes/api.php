<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\ClinicSettingController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\IcdCodeController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\QueueController;
use App\Http\Controllers\Api\QueueSettingController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\MedicineCategoryController;
use App\Http\Controllers\Api\MedicineController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\GoodsReceiptController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\DoctorScheduleController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\MedicalLetterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post("/auth/login", [AuthController::class, "login"]);

// Protected routes (require authentication)
Route::middleware("auth:sanctum")->group(function () {
    // Auth routes
    Route::post("/auth/logout", [AuthController::class, "logout"]);
    Route::get("/auth/me", [AuthController::class, "me"]);
    Route::put("/auth/profile", [AuthController::class, "updateProfile"]);
    Route::put("/auth/password", [AuthController::class, "updatePassword"]);

    // User management routes
    Route::get("/users/stats", [UserController::class, "stats"]);
    Route::get("/users/doctors", [
        UserController::class,
        "getDoctorsByDepartment",
    ]);
    Route::patch("/users/{user}/toggle-status", [
        UserController::class,
        "toggleStatus",
    ]);
    Route::get("/users/{user}/departments", [
        UserController::class,
        "getDepartments",
    ]);
    Route::post("/users/{user}/departments", [
        UserController::class,
        "assignDepartments",
    ]);
    Route::apiResource("/users", UserController::class);

    // Department routes
    Route::get("/departments/stats", [DepartmentController::class, "stats"]);
    Route::get("/departments/active", [DepartmentController::class, "active"]);
    Route::get("/departments/colors", [DepartmentController::class, "colors"]);
    Route::get("/departments/icons", [DepartmentController::class, "icons"]);
    Route::patch("/departments/{department}/toggle-status", [
        DepartmentController::class,
        "toggleStatus",
    ]);
    Route::apiResource("/departments", DepartmentController::class);

    // Service routes
    Route::get("/services/stats", [ServiceController::class, "stats"]);
    Route::get("/services/active", [ServiceController::class, "active"]);
    Route::get("/services/categories", [
        ServiceController::class,
        "categories",
    ]);
    Route::get("/services/colors", [ServiceController::class, "colors"]);
    Route::get("/services/icons", [ServiceController::class, "icons"]);
    Route::patch("/services/{service}/toggle-status", [
        ServiceController::class,
        "toggleStatus",
    ]);
    Route::apiResource("/services", ServiceController::class);

    // ICD Code routes
    Route::get("/icd-codes/stats", [IcdCodeController::class, "stats"]);
    Route::get("/icd-codes/search", [IcdCodeController::class, "search"]);
    Route::get("/icd-codes/types", [IcdCodeController::class, "types"]);
    Route::get("/icd-codes/chapters", [IcdCodeController::class, "chapters"]);
    Route::post("/icd-codes/import", [IcdCodeController::class, "import"]);
    Route::patch("/icd-codes/{icdCode}/toggle-status", [
        IcdCodeController::class,
        "toggleStatus",
    ]);
    Route::apiResource("/icd-codes", IcdCodeController::class);

    // Clinic Settings routes
    Route::get("/clinic-settings", [ClinicSettingController::class, "show"]);
    Route::put("/clinic-settings", [ClinicSettingController::class, "update"]);
    Route::post("/clinic-settings/logo", [
        ClinicSettingController::class,
        "uploadLogo",
    ]);
    Route::post("/clinic-settings/favicon", [
        ClinicSettingController::class,
        "uploadFavicon",
    ]);
    Route::get("/clinic-settings/timezones", [
        ClinicSettingController::class,
        "timezones",
    ]);

    // Role routes
    Route::get("/roles/stats", [RoleController::class, "stats"]);
    Route::get("/roles/active", [RoleController::class, "active"]);
    Route::get("/roles/permissions-matrix", [
        RoleController::class,
        "permissionsMatrix",
    ]);
    Route::patch("/roles/{role}/toggle-status", [
        RoleController::class,
        "toggleStatus",
    ]);
    Route::put("/roles/{role}/permissions", [
        RoleController::class,
        "updatePermissions",
    ]);
    Route::apiResource("/roles", RoleController::class);

    // Permission routes
    Route::get("/permissions/stats", [PermissionController::class, "stats"]);
    Route::get("/permissions/grouped", [
        PermissionController::class,
        "grouped",
    ]);
    Route::get("/permissions/modules", [
        PermissionController::class,
        "modules",
    ]);
    Route::post("/permissions/bulk-create", [
        PermissionController::class,
        "bulkCreate",
    ]);
    Route::apiResource("/permissions", PermissionController::class);

    // Patient routes
    Route::get("/patients/stats", [PatientController::class, "stats"]);
    Route::get("/patients/search", [PatientController::class, "search"]);
    Route::get("/patients/options", [PatientController::class, "options"]);
    Route::post("/patients/generate-mrn", [
        PatientController::class,
        "generateMrn",
    ]);
    Route::patch("/patients/{patient}/toggle-status", [
        PatientController::class,
        "toggleStatus",
    ]);
    Route::get("/patients/{patient}/visits", [
        PatientController::class,
        "visits",
    ]);
    Route::apiResource("/patients", PatientController::class);

    // Queue routes
    Route::get("/queues/stats", [QueueController::class, "stats"]);
    Route::get("/queues/today", [QueueController::class, "today"]);
    Route::get("/queues/display", [QueueController::class, "display"]);
    Route::get("/queues/current/{department}", [
        QueueController::class,
        "current",
    ]);
    Route::post("/queues/take", [QueueController::class, "take"]);
    Route::patch("/queues/{queue}/call", [QueueController::class, "call"]);
    Route::patch("/queues/{queue}/start", [QueueController::class, "start"]);
    Route::patch("/queues/{queue}/complete", [
        QueueController::class,
        "complete",
    ]);
    Route::patch("/queues/{queue}/skip", [QueueController::class, "skip"]);
    Route::patch("/queues/{queue}/cancel", [QueueController::class, "cancel"]);
    Route::patch("/queues/{queue}/assign-patient", [
        QueueController::class,
        "assignPatient",
    ]);
    Route::post("/queues/reset", [QueueController::class, "reset"]);
    Route::apiResource("/queues", QueueController::class)->only([
        "index",
        "show",
    ]);

    // Queue Settings routes
    Route::get("/queue-settings", [QueueSettingController::class, "index"]);
    Route::put("/queue-settings/{department}", [
        QueueSettingController::class,
        "update",
    ]);

    // Medical Record routes
    Route::get("/medical-records/stats", [
        MedicalRecordController::class,
        "stats",
    ]);
    Route::get("/medical-records/pending", [
        MedicalRecordController::class,
        "pending",
    ]);
    Route::get("/medical-records/completed", [
        MedicalRecordController::class,
        "completed",
    ]);
    Route::patch("/medical-records/{medicalRecord}/complete", [
        MedicalRecordController::class,
        "complete",
    ]);
    Route::post("/medical-records/{medicalRecord}/prescription", [
        MedicalRecordController::class,
        "addPrescription",
    ]);
    Route::get("/patients/{patient}/medical-history", [
        MedicalRecordController::class,
        "patientHistory",
    ]);
    Route::apiResource("/medical-records", MedicalRecordController::class);

    // Prescription routes
    Route::patch("/prescriptions/{prescription}/status", [
        PrescriptionController::class,
        "updateStatus",
    ]);
    Route::apiResource("/prescriptions", PrescriptionController::class);

    // Invoice routes
    Route::get("/invoices/stats", [InvoiceController::class, "stats"]);
    Route::get("/invoices/unpaid", [InvoiceController::class, "unpaid"]);
    Route::patch("/invoices/{invoice}/pay", [InvoiceController::class, "pay"]);
    Route::get("/invoices/{invoice}/print", [
        InvoiceController::class,
        "print",
    ]);
    Route::apiResource("/invoices", InvoiceController::class);

    // =====================
    // PHARMACY MODULE
    // =====================

    // Supplier routes
    Route::get("/suppliers/stats", [SupplierController::class, "stats"]);
    Route::get("/suppliers/active", [SupplierController::class, "active"]);
    Route::patch("/suppliers/{supplier}/toggle-status", [
        SupplierController::class,
        "toggleStatus",
    ]);
    Route::apiResource("/suppliers", SupplierController::class);

    // Medicine Category routes
    Route::apiResource(
        "/medicine-categories",
        MedicineCategoryController::class,
    );

    // Medicine routes
    Route::get("/medicines/stats", [MedicineController::class, "stats"]);
    Route::get("/medicines/active", [MedicineController::class, "active"]);
    Route::get("/medicines/low-stock", [MedicineController::class, "lowStock"]);
    Route::get("/medicines/expiring", [MedicineController::class, "expiring"]);
    Route::get("/medicines/units", [MedicineController::class, "units"]);
    Route::post("/medicines/calculate-price", [
        MedicineController::class,
        "calculatePrice",
    ]);
    Route::patch("/medicines/{medicine}/toggle-status", [
        MedicineController::class,
        "toggleStatus",
    ]);
    Route::get("/medicines/{medicine}/stock-card", [
        MedicineController::class,
        "stockCard",
    ]);
    Route::get("/medicines/{medicine}/batches", [
        MedicineController::class,
        "batches",
    ]);
    Route::apiResource("/medicines", MedicineController::class);

    // Purchase Order routes
    Route::get("/purchase-orders/stats", [
        PurchaseOrderController::class,
        "stats",
    ]);
    Route::get("/purchase-orders/pending-approval", [
        PurchaseOrderController::class,
        "pendingApproval",
    ]);
    Route::get("/purchase-orders/needs-receiving", [
        PurchaseOrderController::class,
        "needsReceiving",
    ]);
    Route::patch("/purchase-orders/{purchaseOrder}/submit", [
        PurchaseOrderController::class,
        "submitForApproval",
    ]);
    Route::patch("/purchase-orders/{purchaseOrder}/approve", [
        PurchaseOrderController::class,
        "approve",
    ]);
    Route::patch("/purchase-orders/{purchaseOrder}/reject", [
        PurchaseOrderController::class,
        "reject",
    ]);
    Route::patch("/purchase-orders/{purchaseOrder}/mark-ordered", [
        PurchaseOrderController::class,
        "markAsOrdered",
    ]);
    Route::patch("/purchase-orders/{purchaseOrder}/cancel", [
        PurchaseOrderController::class,
        "cancel",
    ]);
    Route::apiResource("/purchase-orders", PurchaseOrderController::class);

    // Goods Receipt routes
    Route::get("/goods-receipts/stats", [
        GoodsReceiptController::class,
        "stats",
    ]);
    Route::get("/goods-receipts/from-po/{purchaseOrder}", [
        GoodsReceiptController::class,
        "createFromPo",
    ]);
    Route::patch("/goods-receipts/{goodsReceipt}/complete", [
        GoodsReceiptController::class,
        "complete",
    ]);
    Route::patch("/goods-receipts/{goodsReceipt}/cancel", [
        GoodsReceiptController::class,
        "cancel",
    ]);
    Route::apiResource("/goods-receipts", GoodsReceiptController::class);

    // Stock Movement routes
    Route::get("/stock-movements/stats", [
        StockMovementController::class,
        "stats",
    ]);
    Route::get("/stock-movements/reasons", [
        StockMovementController::class,
        "reasons",
    ]);
    Route::get("/stock-movements/summary", [
        StockMovementController::class,
        "summary",
    ]);
    Route::get("/stock-movements/stock-card/{medicine}", [
        StockMovementController::class,
        "stockCard",
    ]);
    Route::post("/stock-movements/adjustment", [
        StockMovementController::class,
        "adjustment",
    ]);
    Route::apiResource(
        "/stock-movements",
        StockMovementController::class,
    )->only(["index", "show"]);

    // =====================
    // DOCTOR SCHEDULE MODULE
    // =====================

    // Doctor Schedule routes
    Route::get("/schedules/available-doctors", [
        DoctorScheduleController::class,
        "availableDoctors",
    ]);
    Route::get("/schedules/day-options", [
        DoctorScheduleController::class,
        "dayOptions",
    ]);
    Route::get("/doctors/{doctor}/schedules", [
        DoctorScheduleController::class,
        "doctorSchedule",
    ]);
    Route::patch("/schedules/{schedule}/toggle-status", [
        DoctorScheduleController::class,
        "toggleStatus",
    ]);
    Route::apiResource("/schedules", DoctorScheduleController::class);

    // =====================
    // REPORTS MODULE
    // =====================
    Route::prefix("reports")->group(function () {
        Route::get("/summary", [ReportController::class, "summary"]);
        Route::get("/visits", [ReportController::class, "visits"]);
        Route::get("/revenue", [ReportController::class, "revenue"]);
        Route::get("/diagnoses", [ReportController::class, "diagnoses"]);
        Route::get("/doctors", [ReportController::class, "doctors"]);
        Route::get("/departments", [ReportController::class, "departments"]);
    });

    // =====================
    // MEDICAL LETTERS MODULE
    // =====================
    Route::get("/medical-letters/stats", [
        MedicalLetterController::class,
        "stats",
    ]);
    Route::get("/medical-letters/{medicalLetter}/print", [
        MedicalLetterController::class,
        "print",
    ]);
    Route::apiResource("/medical-letters", MedicalLetterController::class);
});

// Public route for clinic info (no auth required)
Route::get("/clinic-info", [ClinicSettingController::class, "publicInfo"]);

// Public routes for Kiosk (no auth required)
Route::prefix("kiosk")->group(function () {
    Route::get("/departments", [DepartmentController::class, "active"]);
    Route::get("/departments/{departmentId}/schedules", [
        DoctorScheduleController::class,
        "kioskSchedules",
    ]);
    Route::post("/take-queue", [QueueController::class, "take"]);
    Route::get("/queue-display", [QueueController::class, "display"]);
});
