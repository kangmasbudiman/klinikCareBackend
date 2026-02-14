<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Get all users with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with("departments");

        // Search by name or email
        if ($request->has("search") && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where("name", "like", "%{$search}%")->orWhere(
                    "email",
                    "like",
                    "%{$search}%",
                );
            });
        }

        // Filter by role
        if ($request->has("role") && $request->role) {
            $query->where("role", $request->role);
        }

        // Filter by status
        if ($request->has("status") && $request->status) {
            $isActive = $request->status === "active";
            $query->where("is_active", $isActive);
        }

        // Filter by department
        if ($request->has("department_id") && $request->department_id) {
            $query->whereHas("departments", function ($q) use ($request) {
                $q->where("departments.id", $request->department_id);
            });
        }

        // Order by latest
        $query->orderBy("created_at", "desc");

        // Pagination
        $perPage = $request->get("per_page", 10);
        $users = $query->paginate($perPage);

        return response()->json([
            "success" => true,
            "message" => "Data user berhasil diambil",
            "data" => $users->items(),
            "meta" => [
                "current_page" => $users->currentPage(),
                "last_page" => $users->lastPage(),
                "per_page" => $users->perPage(),
                "total" => $users->total(),
            ],
        ]);
    }

    /**
     * Get user statistics
     */
    public function stats(): JsonResponse
    {
        $total = User::count();
        $active = User::where("is_active", true)->count();
        $inactive = User::where("is_active", false)->count();

        $byRole = [];
        foreach (User::ROLES as $role => $label) {
            $byRole[$role] = User::where("role", $role)->count();
        }

        return response()->json([
            "success" => true,
            "data" => [
                "total" => $total,
                "active" => $active,
                "inactive" => $inactive,
                "by_role" => $byRole,
            ],
        ]);
    }

    /**
     * Get single user
     */
    public function show(User $user): JsonResponse
    {
        $user->load("departments");

        return response()->json([
            "success" => true,
            "message" => "Data user berhasil diambil",
            "data" => $user,
        ]);
    }

    /**
     * Create new user
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
                "name" => ["required", "string", "min:2", "max:100"],
                "email" => ["required", "email", "unique:users,email"],
                "password" => ["required", "string", "min:6", "max:50"],
                "phone" => ["nullable", "string", "max:20"],
                "role" => ["required", Rule::in(array_keys(User::ROLES))],
                "is_active" => ["boolean"],
                "avatar" => ["nullable", "string"],
            ],
            [
                "name.required" => "Nama wajib diisi",
                "name.min" => "Nama minimal 2 karakter",
                "name.max" => "Nama maksimal 100 karakter",
                "email.required" => "Email wajib diisi",
                "email.email" => "Format email tidak valid",
                "email.unique" => "Email sudah digunakan",
                "password.required" => "Password wajib diisi",
                "password.min" => "Password minimal 6 karakter",
                "role.required" => "Role wajib dipilih",
                "role.in" => "Role tidak valid",
            ],
        );

        // Hash password
        $validated["password"] = Hash::make($validated["password"]);

        // Set default avatar if not provided
        if (empty($validated["avatar"])) {
            $validated["avatar"] =
                "https://api.dicebear.com/7.x/avataaars/svg?seed=" .
                urlencode($validated["name"]);
        }

        // Set default is_active
        if (!isset($validated["is_active"])) {
            $validated["is_active"] = true;
        }

        $user = User::create($validated);

        return response()->json(
            [
                "success" => true,
                "message" => "User berhasil dibuat",
                "data" => $user,
            ],
            201,
        );
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate(
            [
                "name" => ["required", "string", "min:2", "max:100"],
                "email" => [
                    "required",
                    "email",
                    Rule::unique("users", "email")->ignore($user->id),
                ],
                "password" => ["nullable", "string", "min:6", "max:50"],
                "phone" => ["nullable", "string", "max:20"],
                "role" => ["required", Rule::in(array_keys(User::ROLES))],
                "is_active" => ["boolean"],
                "avatar" => ["nullable", "string"],
            ],
            [
                "name.required" => "Nama wajib diisi",
                "name.min" => "Nama minimal 2 karakter",
                "name.max" => "Nama maksimal 100 karakter",
                "email.required" => "Email wajib diisi",
                "email.email" => "Format email tidak valid",
                "email.unique" => "Email sudah digunakan",
                "password.min" => "Password minimal 6 karakter",
                "role.required" => "Role wajib dipilih",
                "role.in" => "Role tidak valid",
            ],
        );

        // Hash password if provided
        if (!empty($validated["password"])) {
            $validated["password"] = Hash::make($validated["password"]);
        } else {
            unset($validated["password"]);
        }

        $user->update($validated);

        return response()->json([
            "success" => true,
            "message" => "User berhasil diperbarui",
            "data" => $user->fresh(),
        ]);
    }

    /**
     * Delete user
     */
    public function destroy(User $user): JsonResponse
    {
        // Prevent deleting self
        if (auth()->id() === $user->id) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Tidak dapat menghapus akun sendiri",
                ],
                403,
            );
        }

        $user->delete();

        return response()->json([
            "success" => true,
            "message" => "User berhasil dihapus",
        ]);
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(User $user): JsonResponse
    {
        // Prevent deactivating self
        if (auth()->id() === $user->id) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Tidak dapat mengubah status akun sendiri",
                ],
                403,
            );
        }

        $user->update([
            "is_active" => !$user->is_active,
        ]);

        $message = $user->is_active
            ? "User berhasil diaktifkan"
            : "User berhasil dinonaktifkan";

        return response()->json([
            "success" => true,
            "message" => $message,
            "data" => $user->fresh(),
        ]);
    }

    /**
     * Assign departments to user (for dokter/perawat)
     */
    public function assignDepartments(
        Request $request,
        User $user,
    ): JsonResponse {
        // Only allow for dokter and perawat
        if (!in_array($user->role, [User::ROLE_DOKTER, User::ROLE_PERAWAT])) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Penugasan poli hanya untuk role dokter dan perawat",
                ],
                400,
            );
        }

        $validated = $request->validate(
            [
                "department_ids" => ["required", "array", "min:1"],
                "department_ids.*" => [
                    "required",
                    "integer",
                    "exists:departments,id",
                ],
                "primary_department_id" => [
                    "nullable",
                    "integer",
                    "exists:departments,id",
                ],
            ],
            [
                "department_ids.required" => "Poli wajib dipilih",
                "department_ids.array" => "Format poli tidak valid",
                "department_ids.min" => "Minimal pilih 1 poli",
                "department_ids.*.exists" => "Poli tidak ditemukan",
                "primary_department_id.exists" => "Poli utama tidak ditemukan",
            ],
        );

        // Validate primary is in the list
        $primaryId = $validated["primary_department_id"] ?? null;
        if ($primaryId && !in_array($primaryId, $validated["department_ids"])) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Poli utama harus termasuk dalam daftar poli yang dipilih",
                ],
                400,
            );
        }

        // If no primary specified, use the first one
        if (!$primaryId && count($validated["department_ids"]) > 0) {
            $primaryId = $validated["department_ids"][0];
        }

        $user->assignDepartments($validated["department_ids"], $primaryId);

        return response()->json([
            "success" => true,
            "message" => "Poli berhasil ditugaskan",
            "data" => $user->fresh()->load("departments"),
        ]);
    }

    /**
     * Get user's departments
     */
    public function getDepartments(User $user): JsonResponse
    {
        return response()->json([
            "success" => true,
            "data" => $user->departments()->get(),
        ]);
    }

    /**
     * Get doctors by department
     */
    public function getDoctorsByDepartment(Request $request): JsonResponse
    {
        $query = User::with("departments")
            ->where("role", User::ROLE_DOKTER)
            ->where("is_active", true);

        if ($request->has("department_id") && $request->department_id) {
            $query->whereHas("departments", function ($q) use ($request) {
                $q->where("departments.id", $request->department_id);
            });
        }

        $doctors = $query->orderBy("name")->get();

        return response()->json([
            "success" => true,
            "data" => $doctors,
        ]);
    }
}
