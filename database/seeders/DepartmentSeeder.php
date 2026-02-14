<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'code' => 'POLI-001',
                'name' => 'Poli Umum',
                'description' => 'Pelayanan pemeriksaan kesehatan umum untuk semua usia',
                'icon' => 'stethoscope',
                'color' => 'blue',
                'quota_per_day' => 50,
                'is_active' => true,
            ],
            [
                'code' => 'POLI-002',
                'name' => 'Poli Gigi',
                'description' => 'Pelayanan kesehatan gigi dan mulut',
                'icon' => 'smile',
                'color' => 'cyan',
                'quota_per_day' => 30,
                'is_active' => true,
            ],
            [
                'code' => 'POLI-003',
                'name' => 'Poli Anak',
                'description' => 'Pelayanan kesehatan anak dan balita',
                'icon' => 'baby',
                'color' => 'pink',
                'quota_per_day' => 40,
                'is_active' => true,
            ],
            [
                'code' => 'POLI-004',
                'name' => 'Poli Kandungan',
                'description' => 'Pelayanan kesehatan ibu hamil dan kandungan',
                'icon' => 'heart',
                'color' => 'red',
                'quota_per_day' => 35,
                'is_active' => true,
            ],
            [
                'code' => 'POLI-005',
                'name' => 'Poli Mata',
                'description' => 'Pelayanan pemeriksaan dan pengobatan mata',
                'icon' => 'eye',
                'color' => 'indigo',
                'quota_per_day' => 25,
                'is_active' => true,
            ],
            [
                'code' => 'POLI-006',
                'name' => 'Poli THT',
                'description' => 'Pelayanan kesehatan telinga, hidung, dan tenggorokan',
                'icon' => 'ear',
                'color' => 'orange',
                'quota_per_day' => 25,
                'is_active' => true,
            ],
            [
                'code' => 'POLI-007',
                'name' => 'Poli Kulit',
                'description' => 'Pelayanan kesehatan kulit dan kelamin',
                'icon' => 'user',
                'color' => 'yellow',
                'quota_per_day' => 30,
                'is_active' => true,
            ],
            [
                'code' => 'POLI-008',
                'name' => 'Poli Saraf',
                'description' => 'Pelayanan kesehatan sistem saraf',
                'icon' => 'brain',
                'color' => 'purple',
                'quota_per_day' => 20,
                'is_active' => true,
            ],
            [
                'code' => 'LAB-001',
                'name' => 'Laboratorium',
                'description' => 'Pelayanan pemeriksaan laboratorium dan diagnostik',
                'icon' => 'microscope',
                'color' => 'teal',
                'quota_per_day' => 100,
                'is_active' => true,
            ],
            [
                'code' => 'RAD-001',
                'name' => 'Radiologi',
                'description' => 'Pelayanan rontgen, USG, dan pencitraan medis',
                'icon' => 'activity',
                'color' => 'green',
                'quota_per_day' => 40,
                'is_active' => false,
            ],
        ];

        foreach ($departments as $departmentData) {
            Department::updateOrCreate(
                ['code' => $departmentData['code']],
                $departmentData
            );
        }
    }
}
