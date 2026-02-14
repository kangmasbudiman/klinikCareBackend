<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            'dashboard' => [
                'label' => 'Dashboard',
                'actions' => ['view'],
            ],
            'users' => [
                'label' => 'Pengguna',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'roles' => [
                'label' => 'Hak Akses',
                'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
            ],
            'departments' => [
                'label' => 'Departemen',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'services' => [
                'label' => 'Layanan',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'icd_codes' => [
                'label' => 'Kode ICD',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'clinic_settings' => [
                'label' => 'Pengaturan Klinik',
                'actions' => ['view', 'manage'],
            ],
            'patients' => [
                'label' => 'Pasien',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'appointments' => [
                'label' => 'Janji Temu',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'medical_records' => [
                'label' => 'Rekam Medis',
                'actions' => ['view', 'create', 'edit'],
            ],
            'prescriptions' => [
                'label' => 'Resep',
                'actions' => ['view', 'create', 'edit'],
            ],
            'billing' => [
                'label' => 'Tagihan',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'payments' => [
                'label' => 'Pembayaran',
                'actions' => ['view', 'create'],
            ],
            'pharmacy' => [
                'label' => 'Farmasi',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'inventory' => [
                'label' => 'Inventaris',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'reports' => [
                'label' => 'Laporan',
                'actions' => ['view', 'export'],
            ],
        ];

        $actionLabels = [
            'view' => 'Lihat',
            'create' => 'Tambah',
            'edit' => 'Edit',
            'delete' => 'Hapus',
            'manage' => 'Kelola',
            'export' => 'Export',
        ];

        $sortOrder = 0;

        foreach ($modules as $module => $config) {
            foreach ($config['actions'] as $action) {
                Permission::create([
                    'name' => "{$module}.{$action}",
                    'display_name' => "{$actionLabels[$action]} {$config['label']}",
                    'description' => "Izin untuk {$actionLabels[$action]} data {$config['label']}",
                    'module' => $module,
                    'action' => $action,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
    }
}
