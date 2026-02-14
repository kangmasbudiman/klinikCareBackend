<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user and create token
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            "email" => "required|email",
            "password" => "required|string|min:6",
        ]);

        $user = User::where("email", $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                "email" => ["Email atau password salah."],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                "email" => [
                    "Akun Anda tidak aktif. Silakan hubungi administrator.",
                ],
            ]);
        }

        // Revoke all existing tokens for this user (optional - single device login)
        // $user->tokens()->delete();

        // Create new token
        $token = $user->createToken("auth-token")->plainTextToken;

        return response()->json([
            "success" => true,
            "message" => "Login berhasil",
            "data" => [
                "user" => $this->formatUser($user),
                "token" => $token,
            ],
        ]);
    }

    /**
     * Logout user and revoke token
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            "success" => true,
            "message" => "Logout berhasil",
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Unauthenticated",
                ],
                401,
            );
        }

        return response()->json([
            "success" => true,
            "message" => "Data user berhasil diambil",
            "data" => $this->formatUser($user),
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            "name" => "sometimes|string|max:255",
            "phone" => "sometimes|nullable|string|max:20",
            "avatar" => "sometimes|nullable|string|max:255",
        ]);

        $user->update($validated);

        return response()->json([
            "success" => true,
            "message" => "Profil berhasil diperbarui",
            "data" => $this->formatUser($user->fresh()),
        ]);
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            "current_password" => "required|string",
            "password" => "required|string|min:8|confirmed",
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                "current_password" => ["Password saat ini tidak sesuai."],
            ]);
        }

        $user->update([
            "password" => Hash::make($request->password),
        ]);

        return response()->json([
            "success" => true,
            "message" => "Password berhasil diperbarui",
        ]);
    }

    /**
     * Format user data for response
     */
    private function formatUser(User $user): array
    {
        // Load departments for dokter/perawat
        $departments = [];
        $primaryDepartmentId = null;

        if (in_array($user->role, [User::ROLE_DOKTER, User::ROLE_PERAWAT])) {
            $user->load("departments");
            $departments = $user->departments
                ->map(function ($dept) {
                    return [
                        "id" => $dept->id,
                        "code" => $dept->code,
                        "name" => $dept->name,
                        "color" => $dept->color,
                        "is_primary" => $dept->pivot->is_primary,
                    ];
                })
                ->toArray();

            $primaryDept = collect($departments)->firstWhere(
                "is_primary",
                true,
            );
            $primaryDepartmentId = $primaryDept ? $primaryDept["id"] : null;
        }

        return [
            "id" => $user->id,
            "name" => $user->name,
            "email" => $user->email,
            "role" => $user->role,
            "role_label" => $user->role_label,
            "phone" => $user->phone,
            "avatar" => $user->avatar,
            "is_active" => $user->is_active,
            "departments" => $departments,
            "primary_department_id" => $primaryDepartmentId,
            "email_verified_at" => $user->email_verified_at?->toISOString(),
            "created_at" => $user->created_at->toISOString(),
            "updated_at" => $user->updated_at->toISOString(),
        ];
    }
}
