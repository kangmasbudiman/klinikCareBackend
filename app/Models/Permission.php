<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
        'action',
        'sort_order',
    ];

    /**
     * Get the roles that have this permission
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission')
            ->withTimestamps();
    }

    /**
     * Scope: Filter by module
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope: Filter by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Get all permissions grouped by module
     */
    public static function getGroupedByModule(): array
    {
        return static::orderBy('module')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('module')
            ->toArray();
    }

    /**
     * Get available modules
     */
    public static function getModules(): array
    {
        return static::distinct('module')
            ->orderBy('module')
            ->pluck('module')
            ->toArray();
    }

    /**
     * Get available actions
     */
    public static function getActions(): array
    {
        return ['view', 'create', 'edit', 'delete', 'manage'];
    }

    /**
     * Get module display names
     */
    public static function getModuleLabels(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'users' => 'Pengguna',
            'roles' => 'Hak Akses',
            'departments' => 'Departemen',
            'services' => 'Layanan',
            'icd_codes' => 'Kode ICD',
            'clinic_settings' => 'Pengaturan Klinik',
            'patients' => 'Pasien',
            'appointments' => 'Janji Temu',
            'medical_records' => 'Rekam Medis',
            'billing' => 'Tagihan',
            'pharmacy' => 'Farmasi',
            'reports' => 'Laporan',
        ];
    }

    /**
     * Get action display names
     */
    public static function getActionLabels(): array
    {
        return [
            'view' => 'Lihat',
            'create' => 'Tambah',
            'edit' => 'Edit',
            'delete' => 'Hapus',
            'manage' => 'Kelola',
        ];
    }
}
