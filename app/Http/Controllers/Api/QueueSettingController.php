<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QueueSetting;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QueueSettingController extends Controller
{
    /**
     * Display a listing of queue settings
     */
    public function index(): JsonResponse
    {
        $settings = QueueSetting::with('department')->get();

        // Also get departments without settings
        $departmentsWithSettings = $settings->pluck('department_id')->toArray();
        $departmentsWithoutSettings = Department::whereNotIn('id', $departmentsWithSettings)
            ->where('is_active', true)
            ->get()
            ->map(function ($dept) {
                return [
                    'id' => null,
                    'department_id' => $dept->id,
                    'prefix' => strtoupper(substr($dept->name, 0, 1)),
                    'daily_quota' => 50,
                    'start_number' => 1,
                    'is_active' => false,
                    'department' => $dept,
                    'created_at' => null,
                    'updated_at' => null,
                ];
            });

        $allSettings = $settings->concat($departmentsWithoutSettings);

        return response()->json([
            'success' => true,
            'data' => $allSettings,
        ]);
    }

    /**
     * Update or create queue setting for a department
     */
    public function update(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'prefix' => ['required', 'string', 'max:5'],
            'daily_quota' => ['required', 'integer', 'min:1', 'max:500'],
            'start_number' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
        ]);

        $setting = QueueSetting::updateOrCreate(
            ['department_id' => $department->id],
            $validated
        );

        $setting->load('department');

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan antrian berhasil disimpan',
            'data' => $setting,
        ]);
    }
}
