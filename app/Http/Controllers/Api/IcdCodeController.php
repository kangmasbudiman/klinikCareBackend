<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IcdCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class IcdCodeController extends Controller
{
    /**
     * Get all ICD codes with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = IcdCode::query();

        // Search by code or name
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filter by chapter
        if ($request->has('chapter') && $request->chapter) {
            $query->where('chapter', $request->chapter);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Filter by BPJS claimable
        if ($request->has('bpjs_claimable') && $request->bpjs_claimable !== null) {
            $query->where('is_bpjs_claimable', $request->bpjs_claimable === 'true' || $request->bpjs_claimable === '1');
        }

        // Filter by parent (show only root or children of specific parent)
        if ($request->has('parent_code')) {
            if ($request->parent_code === 'root' || $request->parent_code === '') {
                $query->whereNull('parent_code');
            } else {
                $query->where('parent_code', $request->parent_code);
            }
        }

        // Order
        $query->orderBy('code', 'asc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $icdCodes = $query->paginate($perPage);

        // Add computed attributes
        $items = collect($icdCodes->items())->map(function ($icd) {
            return array_merge($icd->toArray(), [
                'type_label' => $icd->type_label,
                'display_name' => $icd->display_name,
                'full_display' => $icd->full_display,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Data kode ICD berhasil diambil',
            'data' => $items,
            'meta' => [
                'current_page' => $icdCodes->currentPage(),
                'last_page' => $icdCodes->lastPage(),
                'per_page' => $icdCodes->perPage(),
                'total' => $icdCodes->total(),
            ],
        ]);
    }

    /**
     * Get ICD code statistics
     */
    public function stats(): JsonResponse
    {
        $total = IcdCode::count();
        $active = IcdCode::active()->count();
        $inactive = IcdCode::where('is_active', false)->count();
        $icd10Count = IcdCode::icd10()->count();
        $icd9cmCount = IcdCode::icd9cm()->count();
        $bpjsClaimable = IcdCode::bpjsClaimable()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
                'icd10_count' => $icd10Count,
                'icd9cm_count' => $icd9cmCount,
                'bpjs_claimable' => $bpjsClaimable,
            ],
        ]);
    }

    /**
     * Search ICD codes (for autocomplete/select)
     */
    public function search(Request $request): JsonResponse
    {
        $query = IcdCode::active();

        if ($request->has('q') && $request->q) {
            $query->search($request->q);
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        $limit = $request->get('limit', 20);
        $icdCodes = $query->orderBy('code')
            ->limit($limit)
            ->get(['id', 'code', 'type', 'name_id', 'name_en', 'chapter']);

        $items = $icdCodes->map(function ($icd) {
            return [
                'id' => $icd->id,
                'code' => $icd->code,
                'type' => $icd->type,
                'name_id' => $icd->name_id,
                'name_en' => $icd->name_en,
                'display' => "{$icd->code} - {$icd->name_id}",
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Get single ICD code
     */
    public function show(IcdCode $icdCode): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Data kode ICD berhasil diambil',
            'data' => array_merge($icdCode->toArray(), [
                'type_label' => $icdCode->type_label,
                'display_name' => $icdCode->display_name,
                'full_display' => $icdCode->full_display,
                'children_count' => $icdCode->children()->count(),
            ]),
        ]);
    }

    /**
     * Create new ICD code
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('icd_codes')->where(function ($query) use ($request) {
                    return $query->where('type', $request->type ?? 'icd10');
                }),
            ],
            'type' => ['required', 'in:icd10,icd9cm'],
            'name_id' => ['required', 'string', 'max:500'],
            'name_en' => ['nullable', 'string', 'max:500'],
            'chapter' => ['nullable', 'string', 'max:10'],
            'chapter_name' => ['nullable', 'string', 'max:255'],
            'block' => ['nullable', 'string', 'max:20'],
            'block_name' => ['nullable', 'string', 'max:255'],
            'parent_code' => ['nullable', 'string', 'max:20'],
            'dtd_code' => ['nullable', 'string', 'max:20'],
            'is_bpjs_claimable' => ['boolean'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ], [
            'code.required' => 'Kode ICD wajib diisi',
            'code.unique' => 'Kode ICD sudah digunakan',
            'type.required' => 'Tipe ICD wajib dipilih',
            'type.in' => 'Tipe ICD tidak valid',
            'name_id.required' => 'Nama (Indonesia) wajib diisi',
        ]);

        // Set defaults
        $validated['is_bpjs_claimable'] = $validated['is_bpjs_claimable'] ?? true;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $icdCode = IcdCode::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kode ICD berhasil dibuat',
            'data' => array_merge($icdCode->toArray(), [
                'type_label' => $icdCode->type_label,
                'display_name' => $icdCode->display_name,
            ]),
        ], 201);
    }

    /**
     * Update ICD code
     */
    public function update(Request $request, IcdCode $icdCode): JsonResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('icd_codes')->where(function ($query) use ($request, $icdCode) {
                    return $query->where('type', $request->type ?? $icdCode->type);
                })->ignore($icdCode->id),
            ],
            'type' => ['required', 'in:icd10,icd9cm'],
            'name_id' => ['required', 'string', 'max:500'],
            'name_en' => ['nullable', 'string', 'max:500'],
            'chapter' => ['nullable', 'string', 'max:10'],
            'chapter_name' => ['nullable', 'string', 'max:255'],
            'block' => ['nullable', 'string', 'max:20'],
            'block_name' => ['nullable', 'string', 'max:255'],
            'parent_code' => ['nullable', 'string', 'max:20'],
            'dtd_code' => ['nullable', 'string', 'max:20'],
            'is_bpjs_claimable' => ['boolean'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ], [
            'code.required' => 'Kode ICD wajib diisi',
            'code.unique' => 'Kode ICD sudah digunakan',
            'type.required' => 'Tipe ICD wajib dipilih',
            'type.in' => 'Tipe ICD tidak valid',
            'name_id.required' => 'Nama (Indonesia) wajib diisi',
        ]);

        $icdCode->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kode ICD berhasil diperbarui',
            'data' => array_merge($icdCode->fresh()->toArray(), [
                'type_label' => $icdCode->type_label,
                'display_name' => $icdCode->display_name,
            ]),
        ]);
    }

    /**
     * Delete ICD code
     */
    public function destroy(IcdCode $icdCode): JsonResponse
    {
        // Check if has children
        if ($icdCode->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus kode ICD yang memiliki sub-kode',
            ], 422);
        }

        $icdCode->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kode ICD berhasil dihapus',
        ]);
    }

    /**
     * Toggle ICD code active status
     */
    public function toggleStatus(IcdCode $icdCode): JsonResponse
    {
        $icdCode->update([
            'is_active' => !$icdCode->is_active,
        ]);

        $message = $icdCode->is_active
            ? 'Kode ICD berhasil diaktifkan'
            : 'Kode ICD berhasil dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $icdCode->fresh(),
        ]);
    }

    /**
     * Get type options
     */
    public function types(): JsonResponse
    {
        $types = collect(IcdCode::TYPES)->map(function ($label, $value) {
            return ['value' => $value, 'label' => $label];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Get chapter options for ICD-10
     */
    public function chapters(): JsonResponse
    {
        $chapters = collect(IcdCode::ICD10_CHAPTERS)->map(function ($label, $value) {
            return ['value' => $value, 'label' => "{$value} - {$label}"];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $chapters,
        ]);
    }

    /**
     * Import ICD codes from CSV/JSON
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,json', 'max:10240'],
            'type' => ['required', 'in:icd10,icd9cm'],
        ]);

        $file = $request->file('file');
        $type = $request->type;
        $extension = $file->getClientOriginalExtension();

        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            if ($extension === 'json') {
                $data = json_decode(file_get_contents($file->getRealPath()), true);
            } else {
                // CSV parsing
                $data = [];
                $handle = fopen($file->getRealPath(), 'r');
                $headers = fgetcsv($handle);

                while (($row = fgetcsv($handle)) !== false) {
                    $data[] = array_combine($headers, $row);
                }
                fclose($handle);
            }

            foreach ($data as $index => $row) {
                try {
                    // Check if code already exists
                    $exists = IcdCode::where('code', $row['code'] ?? '')
                        ->where('type', $type)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    IcdCode::create([
                        'code' => $row['code'],
                        'type' => $type,
                        'name_id' => $row['name_id'] ?? $row['name'] ?? '',
                        'name_en' => $row['name_en'] ?? null,
                        'chapter' => $row['chapter'] ?? null,
                        'chapter_name' => $row['chapter_name'] ?? null,
                        'block' => $row['block'] ?? null,
                        'block_name' => $row['block_name'] ?? null,
                        'parent_code' => $row['parent_code'] ?? null,
                        'is_active' => true,
                        'is_bpjs_claimable' => true,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Import selesai. {$imported} data berhasil diimport, {$skipped} data dilewati (sudah ada)",
                'data' => [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'errors' => array_slice($errors, 0, 10), // Limit error messages
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal import data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
