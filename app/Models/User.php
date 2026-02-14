<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the roles for this user
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, "user_role")->withTimestamps();
    }

    /**
     * Get all permissions for this user through roles
     */
    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        return $this->roles()
            ->with("permissions")
            ->get()
            ->flatMap(fn($role) => $role->permissions)
            ->unique("id");
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permissionName): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->roles()
            ->whereHas(
                "permissions",
                fn($q) => $q->where("name", $permissionName),
            )
            ->exists();
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->roles()
            ->whereHas(
                "permissions",
                fn($q) => $q->whereIn("name", $permissions),
            )
            ->exists();
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $userPermissions = $this->getAllPermissions()->pluck("name")->toArray();
        return count(array_intersect($permissions, $userPermissions)) ===
            count($permissions);
    }

    /**
     * Assign roles to user
     */
    public function assignRoles(array $roleIds): void
    {
        $this->roles()->sync($roleIds);
    }

    /**
     * Check if user has a specific role by name
     */
    public function hasRoleByName(string $roleName): bool
    {
        return $this->roles()->where("name", $roleName)->exists();
    }

    /**
     * User roles
     */
    const ROLE_SUPER_ADMIN = "super_admin";
    const ROLE_ADMIN_KLINIK = "admin_klinik";
    const ROLE_DOKTER = "dokter";
    const ROLE_PERAWAT = "perawat";
    const ROLE_KASIR = "kasir";
    const ROLE_PASIEN = "pasien";
    const ROLE_APOTEKER = "apoteker";

    const ROLES = [
        self::ROLE_SUPER_ADMIN => "Super Admin",
        self::ROLE_ADMIN_KLINIK => "Admin Klinik",
        self::ROLE_DOKTER => "Dokter",
        self::ROLE_PERAWAT => "Perawat",
        self::ROLE_KASIR => "Kasir",
        self::ROLE_PASIEN => "Pasien",
        self::ROLE_APOTEKER => "Apoteker",
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        "name",
        "email",
        "password",
        "role",
        "phone",
        "avatar",
        "is_active",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = ["password", "remember_token"];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
            "is_active" => "boolean",
        ];
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user is admin (super admin or admin klinik)
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN_KLINIK,
        ]);
    }

    /**
     * Get role label
     */
    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    /**
     * Get the departments this user belongs to (for dokter/perawat)
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, "user_departments")
            ->withPivot("is_primary")
            ->withTimestamps();
    }

    /**
     * Get primary department
     */
    public function primaryDepartment()
    {
        return $this->departments()->wherePivot("is_primary", true)->first();
    }

    /**
     * Get primary department ID
     */
    public function getPrimaryDepartmentIdAttribute(): ?int
    {
        $primary = $this->departments()
            ->wherePivot("is_primary", true)
            ->first();
        return $primary ? $primary->id : null;
    }

    /**
     * Check if user belongs to a specific department
     */
    public function belongsToDepartment(int $departmentId): bool
    {
        return $this->departments()
            ->where("departments.id", $departmentId)
            ->exists();
    }

    /**
     * Assign departments to user
     */
    public function assignDepartments(
        array $departmentIds,
        ?int $primaryDepartmentId = null,
    ): void {
        $syncData = [];
        foreach ($departmentIds as $deptId) {
            $syncData[$deptId] = [
                "is_primary" => $primaryDepartmentId
                    ? $deptId == $primaryDepartmentId
                    : false,
            ];
        }
        $this->departments()->sync($syncData);
    }

    /**
     * Check if user is a doctor
     */
    public function isDoctor(): bool
    {
        return $this->role === self::ROLE_DOKTER;
    }

    /**
     * Check if user is a nurse
     */
    public function isNurse(): bool
    {
        return $this->role === self::ROLE_PERAWAT;
    }

    /**
     * Check if user is a pharmacist
     */
    public function isPharmacist(): bool
    {
        return $this->role === self::ROLE_APOTEKER;
    }
}
