<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    /**
     * Get all permissions with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query()->withCount('roles');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by module
        if ($request->filled('module') && $request->module !== 'all') {
            $query->where('module', $request->module);
        }

        // Filter by action
        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        // Sort
        $query->orderBy('module')->orderBy('sort_order');

        $perPage = $request->get('per_page', 50);
        $permissions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Permissions retrieved successfully',
            'data' => $permissions->items(),
            'meta' => [
                'current_page' => $permissions->currentPage(),
                'last_page' => $permissions->lastPage(),
                'per_page' => $permissions->perPage(),
                'total' => $permissions->total(),
            ],
        ]);
    }

    /**
     * Get all permissions grouped by module
     */
    public function grouped(): JsonResponse
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

    /**
     * Get permission statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Permission::count(),
            'modules' => Permission::distinct('module')->count('module'),
            'by_module' => Permission::selectRaw('module, COUNT(*) as count')
                ->groupBy('module')
                ->pluck('count', 'module'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get available modules
     */
    public function modules(): JsonResponse
    {
        $modules = Permission::getModules();
        $labels = Permission::getModuleLabels();

        $result = array_map(function ($module) use ($labels) {
            return [
                'value' => $module,
                'label' => $labels[$module] ?? ucfirst($module),
            ];
        }, $modules);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get single permission
     */
    public function show(Permission $permission): JsonResponse
    {
        $permission->load('roles:id,name,display_name');
        $permission->loadCount('roles');

        return response()->json([
            'success' => true,
            'message' => 'Permission retrieved successfully',
            'data' => $permission,
        ]);
    }

    /**
     * Create new permission
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:permissions,name',
            'display_name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'module' => 'required|string|max:50',
            'action' => 'required|string|max:50',
            'sort_order' => 'integer|min:0',
        ]);

        $permission = Permission::create($validated);
        $permission->loadCount('roles');

        return response()->json([
            'success' => true,
            'message' => 'Permission created successfully',
            'data' => $permission,
        ], 201);
    }

    /**
     * Update existing permission
     */
    public function update(Request $request, Permission $permission): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('permissions', 'name')->ignore($permission->id)],
            'display_name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'module' => 'required|string|max:50',
            'action' => 'required|string|max:50',
            'sort_order' => 'integer|min:0',
        ]);

        $permission->update($validated);
        $permission->loadCount('roles');

        return response()->json([
            'success' => true,
            'message' => 'Permission updated successfully',
            'data' => $permission,
        ]);
    }

    /**
     * Delete permission
     */
    public function destroy(Permission $permission): JsonResponse
    {
        // Check if permission is assigned to any roles
        if ($permission->roles()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete permission that is assigned to roles',
            ], 422);
        }

        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission deleted successfully',
        ]);
    }

    /**
     * Bulk create permissions for a module
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'module' => 'required|string|max:50',
            'module_label' => 'required|string|max:100',
            'actions' => 'required|array|min:1',
            'actions.*' => 'string|in:view,create,edit,delete,manage',
        ]);

        $actionLabels = Permission::getActionLabels();
        $created = [];

        foreach ($validated['actions'] as $index => $action) {
            $name = "{$validated['module']}.{$action}";

            // Skip if already exists
            if (Permission::where('name', $name)->exists()) {
                continue;
            }

            $permission = Permission::create([
                'name' => $name,
                'display_name' => "{$actionLabels[$action]} {$validated['module_label']}",
                'description' => "Izin untuk {$actionLabels[$action]} data {$validated['module_label']}",
                'module' => $validated['module'],
                'action' => $action,
                'sort_order' => $index,
            ]);

            $created[] = $permission;
        }

        return response()->json([
            'success' => true,
            'message' => count($created) . ' permissions created successfully',
            'data' => $created,
        ], 201);
    }
}
