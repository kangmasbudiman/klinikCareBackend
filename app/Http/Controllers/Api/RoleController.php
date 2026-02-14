<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Get all roles with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::query()->withCount(['permissions', 'users']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 10);
        $roles = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Roles retrieved successfully',
            'data' => $roles->items(),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
            ],
        ]);
    }

    /**
     * Get active roles for dropdown
     */
    public function active(): JsonResponse
    {
        $roles = Role::active()
            ->orderBy('display_name')
            ->get(['id', 'name', 'display_name', 'color']);

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Get role statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Role::count(),
            'active' => Role::where('is_active', true)->count(),
            'inactive' => Role::where('is_active', false)->count(),
            'system' => Role::where('is_system', true)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get single role by ID
     */
    public function show(Role $role): JsonResponse
    {
        $role->load(['permissions', 'users:id,name,email']);
        $role->loadCount(['permissions', 'users']);

        return response()->json([
            'success' => true,
            'message' => 'Role retrieved successfully',
            'data' => $role,
        ]);
    }

    /**
     * Create new role
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? 'blue',
            'is_active' => $validated['is_active'] ?? true,
            'is_system' => false,
        ]);

        // Sync permissions
        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        $role->load('permissions');
        $role->loadCount(['permissions', 'users']);

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully',
            'data' => $role,
        ], 201);
    }

    /**
     * Update existing role
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($role->id)],
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        // Prevent modifying system role name
        if ($role->is_system && $validated['name'] !== $role->name) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change name of system role',
            ], 422);
        }

        $role->update([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? $role->color,
            'is_active' => $validated['is_active'] ?? $role->is_active,
        ]);

        // Sync permissions
        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        $role->load('permissions');
        $role->loadCount(['permissions', 'users']);

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => $role,
        ]);
    }

    /**
     * Delete role
     */
    public function destroy(Role $role): JsonResponse
    {
        // Prevent deleting system roles
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete system role',
            ], 422);
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role that has users assigned',
            ], 422);
        }

        $role->permissions()->detach();
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully',
        ]);
    }

    /**
     * Toggle role active status
     */
    public function toggleStatus(Role $role): JsonResponse
    {
        // Prevent deactivating system roles
        if ($role->is_system && $role->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate system role',
            ], 422);
        }

        $role->update(['is_active' => !$role->is_active]);
        $role->loadCount(['permissions', 'users']);

        return response()->json([
            'success' => true,
            'message' => $role->is_active ? 'Role activated' : 'Role deactivated',
            'data' => $role,
        ]);
    }

    /**
     * Update permissions for a role
     */
    public function updatePermissions(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role->syncPermissions($validated['permissions']);
        $role->load('permissions');
        $role->loadCount(['permissions', 'users']);

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully',
            'data' => $role,
        ]);
    }

    /**
     * Get permissions matrix for role management
     */
    public function permissionsMatrix(): JsonResponse
    {
        $permissions = Permission::orderBy('module')
            ->orderBy('sort_order')
            ->get();

        $grouped = $permissions->groupBy('module')->map(function ($perms, $module) {
            return [
                'module' => $module,
                'label' => Permission::getModuleLabels()[$module] ?? ucfirst($module),
                'permissions' => $perms->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }
}
