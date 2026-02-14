<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    /**
     * Display a listing of patients
     */
    public function index(Request $request): JsonResponse
    {
        $query = Patient::query();

        // Search filter
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Status filter
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Patient type filter
        if ($request->has('patient_type') && $request->patient_type) {
            $query->where('patient_type', $request->patient_type);
        }

        // Gender filter
        if ($request->has('gender') && $request->gender) {
            $query->where('gender', $request->gender);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Paginate
        $perPage = $request->get('per_page', 10);
        $patients = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $patients->items(),
            'meta' => [
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'per_page' => $patients->perPage(),
                'total' => $patients->total(),
            ],
        ]);
    }

    /**
     * Search patients (quick search for autocomplete)
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $patients = Patient::active()
            ->search($query)
            ->limit($limit)
            ->get(['id', 'medical_record_number', 'nik', 'bpjs_number', 'name', 'birth_date', 'gender', 'patient_type', 'phone']);

        return response()->json([
            'success' => true,
            'data' => $patients,
        ]);
    }

    /**
     * Get patient statistics
     */
    public function stats(): JsonResponse
    {
        $total = Patient::count();
        $active = Patient::where('is_active', true)->count();
        $inactive = Patient::where('is_active', false)->count();
        $umum = Patient::where('patient_type', 'umum')->count();
        $bpjs = Patient::where('patient_type', 'bpjs')->count();
        $asuransi = Patient::where('patient_type', 'asuransi')->count();
        $thisMonth = Patient::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
                'by_type' => [
                    'umum' => $umum,
                    'bpjs' => $bpjs,
                    'asuransi' => $asuransi,
                ],
                'this_month' => $thisMonth,
            ],
        ]);
    }

    /**
     * Generate new medical record number
     */
    public function generateMrn(): JsonResponse
    {
        $mrn = Patient::generateMedicalRecordNumber();

        return response()->json([
            'success' => true,
            'data' => [
                'medical_record_number' => $mrn,
            ],
        ]);
    }

    /**
     * Store a newly created patient
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nik' => ['nullable', 'string', 'size:16', 'unique:patients,nik'],
            'bpjs_number' => ['nullable', 'string', 'max:13'],
            'name' => ['required', 'string', 'max:255'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'blood_type' => ['nullable', Rule::in(['A', 'B', 'AB', 'O'])],
            'religion' => ['nullable', Rule::in(['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu', 'lainnya'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'occupation' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'village' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'allergies' => ['nullable', 'string'],
            'medical_notes' => ['nullable', 'string'],
            'patient_type' => ['required', Rule::in(['umum', 'bpjs', 'asuransi'])],
            'insurance_name' => ['nullable', 'string', 'max:255'],
            'insurance_number' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'string'],
        ]);

        // Generate medical record number
        $validated['medical_record_number'] = Patient::generateMedicalRecordNumber();

        $patient = Patient::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pasien berhasil didaftarkan',
            'data' => $patient,
        ], 201);
    }

    /**
     * Display the specified patient
     */
    public function show(Patient $patient): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $patient,
        ]);
    }

    /**
     * Update the specified patient
     */
    public function update(Request $request, Patient $patient): JsonResponse
    {
        $validated = $request->validate([
            'nik' => ['nullable', 'string', 'size:16', Rule::unique('patients', 'nik')->ignore($patient->id)],
            'bpjs_number' => ['nullable', 'string', 'max:13'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['sometimes', 'required', 'date'],
            'gender' => ['sometimes', 'required', Rule::in(['male', 'female'])],
            'blood_type' => ['nullable', Rule::in(['A', 'B', 'AB', 'O'])],
            'religion' => ['nullable', Rule::in(['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu', 'lainnya'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'occupation' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'village' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'allergies' => ['nullable', 'string'],
            'medical_notes' => ['nullable', 'string'],
            'patient_type' => ['sometimes', 'required', Rule::in(['umum', 'bpjs', 'asuransi'])],
            'insurance_name' => ['nullable', 'string', 'max:255'],
            'insurance_number' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $patient->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data pasien berhasil diperbarui',
            'data' => $patient->fresh(),
        ]);
    }

    /**
     * Remove the specified patient
     */
    public function destroy(Patient $patient): JsonResponse
    {
        // Check if patient has queues
        if ($patient->queues()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Pasien tidak dapat dihapus karena memiliki riwayat kunjungan',
            ], 422);
        }

        $patient->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pasien berhasil dihapus',
        ]);
    }

    /**
     * Toggle patient status
     */
    public function toggleStatus(Patient $patient): JsonResponse
    {
        $patient->update([
            'is_active' => !$patient->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => $patient->is_active ? 'Pasien diaktifkan' : 'Pasien dinonaktifkan',
            'data' => $patient->fresh(),
        ]);
    }

    /**
     * Get patient visits history
     */
    public function visits(Patient $patient): JsonResponse
    {
        $visits = $patient->queues()
            ->with(['department', 'service', 'servedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $visits->items(),
            'meta' => [
                'current_page' => $visits->currentPage(),
                'last_page' => $visits->lastPage(),
                'per_page' => $visits->perPage(),
                'total' => $visits->total(),
            ],
        ]);
    }

    /**
     * Get options for dropdowns
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'genders' => Patient::GENDER_LABELS,
                'patient_types' => Patient::PATIENT_TYPE_LABELS,
                'religions' => Patient::RELIGION_LABELS,
                'marital_statuses' => Patient::MARITAL_STATUS_LABELS,
                'blood_types' => Patient::BLOOD_TYPE_LABELS,
            ],
        ]);
    }
}
