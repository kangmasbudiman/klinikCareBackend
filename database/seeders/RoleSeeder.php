<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all permissions
        $allPermissions = Permission::pluck('id', 'name')->toArray();

        // Define roles with their permissions
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'description' => 'Akses penuh ke seluruh sistem',
                'color' => 'red',
                'is_system' => true,
                'permissions' => array_keys($allPermissions), // All permissions
            ],
            [
                'name' => 'admin_klinik',
                'display_name' => 'Admin Klinik',
                'description' => 'Mengelola operasional klinik sehari-hari',
                'color' => 'blue',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'users.view', 'users.create', 'users.edit',
                    'departments.view', 'departments.create', 'departments.edit', 'departments.delete',
                    'services.view', 'services.create', 'services.edit', 'services.delete',
                    'icd_codes.view', 'icd_codes.create', 'icd_codes.edit',
                    'clinic_settings.view', 'clinic_settings.manage',
                    'patients.view', 'patients.create', 'patients.edit',
                    'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.delete',
                    'billing.view', 'billing.create', 'billing.edit',
                    'payments.view', 'payments.create',
                    'pharmacy.view',
                    'inventory.view', 'inventory.create', 'inventory.edit',
                    'reports.view', 'reports.export',
                ],
            ],
            [
                'name' => 'dokter',
                'display_name' => 'Dokter',
                'description' => 'Akses untuk dokter praktik',
                'color' => 'green',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'departments.view',
                    'services.view',
                    'icd_codes.view',
                    'patients.view', 'patients.create', 'patients.edit',
                    'appointments.view', 'appointments.edit',
                    'medical_records.view', 'medical_records.create', 'medical_records.edit',
                    'prescriptions.view', 'prescriptions.create', 'prescriptions.edit',
                    'billing.view',
                    'reports.view',
                ],
            ],
            [
                'name' => 'perawat',
                'display_name' => 'Perawat',
                'description' => 'Akses untuk perawat/asisten medis',
                'color' => 'cyan',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'departments.view',
                    'services.view',
                    'patients.view', 'patients.edit',
                    'appointments.view', 'appointments.edit',
                    'medical_records.view', 'medical_records.create',
                ],
            ],
            [
                'name' => 'kasir',
                'display_name' => 'Kasir',
                'description' => 'Akses untuk bagian kasir/pembayaran',
                'color' => 'yellow',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'services.view',
                    'patients.view',
                    'billing.view', 'billing.create', 'billing.edit',
                    'payments.view', 'payments.create',
                    'reports.view',
                ],
            ],
            [
                'name' => 'apoteker',
                'display_name' => 'Apoteker',
                'description' => 'Akses untuk bagian farmasi',
                'color' => 'purple',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'patients.view',
                    'prescriptions.view',
                    'pharmacy.view', 'pharmacy.create', 'pharmacy.edit', 'pharmacy.delete',
                    'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.delete',
                    'reports.view',
                ],
            ],
            [
                'name' => 'pendaftaran',
                'display_name' => 'Pendaftaran',
                'description' => 'Akses untuk bagian pendaftaran/registrasi',
                'color' => 'orange',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'departments.view',
                    'services.view',
                    'patients.view', 'patients.create', 'patients.edit',
                    'appointments.view', 'appointments.create', 'appointments.edit',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissionNames = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::create($roleData);

            // Attach permissions
            $permissionIds = [];
            foreach ($permissionNames as $permName) {
                if (isset($allPermissions[$permName])) {
                    $permissionIds[] = $allPermissions[$permName];
                }
            }
            $role->permissions()->attach($permissionIds);
        }
    }
}
