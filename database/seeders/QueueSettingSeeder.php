<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QueueSetting;
use App\Models\Department;

class QueueSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define default settings per department code
        $settings = [
            'POLI-001' => ['prefix' => 'A', 'daily_quota' => 50, 'is_active' => true],    // Poli Umum
            'POLI-002' => ['prefix' => 'B', 'daily_quota' => 30, 'is_active' => true],    // Poli Gigi
            'POLI-003' => ['prefix' => 'C', 'daily_quota' => 40, 'is_active' => true],    // Poli Anak
            'POLI-004' => ['prefix' => 'D', 'daily_quota' => 25, 'is_active' => true],    // Poli Kandungan
            'POLI-005' => ['prefix' => 'E', 'daily_quota' => 30, 'is_active' => true],    // Poli Mata
            'POLI-006' => ['prefix' => 'F', 'daily_quota' => 30, 'is_active' => true],    // Poli THT
            'POLI-007' => ['prefix' => 'G', 'daily_quota' => 30, 'is_active' => true],    // Poli Kulit
            'LAB-001' => ['prefix' => 'L', 'daily_quota' => 100, 'is_active' => true],    // Laboratorium
            'RAD-001' => ['prefix' => 'R', 'daily_quota' => 50, 'is_active' => true],     // Radiologi
            'FARM-001' => ['prefix' => 'P', 'daily_quota' => 200, 'is_active' => true],   // Farmasi
        ];

        foreach ($settings as $deptCode => $setting) {
            $department = Department::where('code', $deptCode)->first();

            if ($department) {
                QueueSetting::updateOrCreate(
                    ['department_id' => $department->id],
                    [
                        'prefix' => $setting['prefix'],
                        'daily_quota' => $setting['daily_quota'],
                        'start_number' => 1,
                        'is_active' => $setting['is_active'],
                    ]
                );
            }
        }
    }
}
