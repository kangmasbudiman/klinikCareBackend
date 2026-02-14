<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalLetter;
use App\Models\ClinicSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MedicalLetterController extends Controller
{
    /**
     * Display a listing of medical letters
     */
    public function index(Request $request): JsonResponse
    {
        $query = MedicalLetter::with(['patient', 'doctor', 'creator']);

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('letter_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('medical_record_number', 'like', "%{$search}%");
                    });
            });
        }

        // Letter type filter
        if ($request->has('letter_type') && $request->letter_type) {
            $query->where('letter_type', $request->letter_type);
        }

        // Patient filter
        if ($request->has('patient_id') && $request->patient_id) {
            $query->where('patient_id', $request->patient_id);
        }

        // Doctor filter
        if ($request->has('doctor_id') && $request->doctor_id) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Date range filter
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('letter_date', [$request->start_date, $request->end_date]);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 15);
        $letters = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $letters->items(),
            'meta' => [
                'current_page' => $letters->currentPage(),
                'last_page' => $letters->lastPage(),
                'per_page' => $letters->perPage(),
                'total' => $letters->total(),
            ],
        ]);
    }

    /**
     * Store a newly created medical letter
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'letter_type' => ['required', 'in:surat_sehat,surat_sakit,surat_rujukan,surat_keterangan'],
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['required', 'exists:users,id'],
            'medical_record_id' => ['nullable', 'exists:medical_records,id'],
            'letter_date' => ['required', 'date'],
            'purpose' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            // Surat Sakit
            'sick_start_date' => ['nullable', 'date'],
            'sick_end_date' => ['nullable', 'date', 'after_or_equal:sick_start_date'],
            'sick_days' => ['nullable', 'integer', 'min:1'],
            // Surat Rujukan
            'referral_destination' => ['nullable', 'string', 'max:255'],
            'referral_specialist' => ['nullable', 'string', 'max:255'],
            'referral_reason' => ['nullable', 'string'],
            'diagnosis_summary' => ['nullable', 'string'],
            'treatment_summary' => ['nullable', 'string'],
            // Surat Sehat
            'health_purpose' => ['nullable', 'string', 'max:255'],
            'examination_result' => ['nullable', 'string'],
            // Surat Keterangan
            'statement_content' => ['nullable', 'string'],
        ]);

        $validated['letter_number'] = MedicalLetter::generateLetterNumber($validated['letter_type']);
        $validated['created_by'] = auth()->id();

        $letter = MedicalLetter::create($validated);
        $letter->load(['patient', 'doctor', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Surat berhasil dibuat',
            'data' => $letter,
        ], 201);
    }

    /**
     * Display the specified medical letter
     */
    public function show(MedicalLetter $medicalLetter): JsonResponse
    {
        $medicalLetter->load(['patient', 'doctor', 'medicalRecord', 'creator']);

        return response()->json([
            'success' => true,
            'data' => $medicalLetter,
        ]);
    }

    /**
     * Update the specified medical letter
     */
    public function update(Request $request, MedicalLetter $medicalLetter): JsonResponse
    {
        $validated = $request->validate([
            'letter_type' => ['sometimes', 'in:surat_sehat,surat_sakit,surat_rujukan,surat_keterangan'],
            'patient_id' => ['sometimes', 'exists:patients,id'],
            'doctor_id' => ['sometimes', 'exists:users,id'],
            'medical_record_id' => ['nullable', 'exists:medical_records,id'],
            'letter_date' => ['sometimes', 'date'],
            'purpose' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'sick_start_date' => ['nullable', 'date'],
            'sick_end_date' => ['nullable', 'date', 'after_or_equal:sick_start_date'],
            'sick_days' => ['nullable', 'integer', 'min:1'],
            'referral_destination' => ['nullable', 'string', 'max:255'],
            'referral_specialist' => ['nullable', 'string', 'max:255'],
            'referral_reason' => ['nullable', 'string'],
            'diagnosis_summary' => ['nullable', 'string'],
            'treatment_summary' => ['nullable', 'string'],
            'health_purpose' => ['nullable', 'string', 'max:255'],
            'examination_result' => ['nullable', 'string'],
            'statement_content' => ['nullable', 'string'],
        ]);

        $medicalLetter->update($validated);
        $medicalLetter->load(['patient', 'doctor', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Surat berhasil diperbarui',
            'data' => $medicalLetter,
        ]);
    }

    /**
     * Remove the specified medical letter
     */
    public function destroy(MedicalLetter $medicalLetter): JsonResponse
    {
        $medicalLetter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Surat berhasil dihapus',
        ]);
    }

    /**
     * Get letter statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $total = MedicalLetter::count();
        $thisMonth = MedicalLetter::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $byType = [];
        foreach (MedicalLetter::LETTER_TYPE_LABELS as $type => $label) {
            $byType[$type] = MedicalLetter::where('letter_type', $type)->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'this_month' => $thisMonth,
                'by_type' => $byType,
            ],
        ]);
    }

    /**
     * Get letter data for printing
     */
    public function print(MedicalLetter $medicalLetter): JsonResponse
    {
        $medicalLetter->load(['patient', 'doctor', 'medicalRecord.diagnoses', 'creator']);

        $clinicSettings = ClinicSetting::first();

        return response()->json([
            'success' => true,
            'data' => [
                'letter' => $medicalLetter,
                'clinic' => $clinicSettings,
            ],
        ]);
    }
}
