<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ClinicSettingController extends Controller
{
    /**
     * Get clinic settings
     */
    public function show(): JsonResponse
    {
        $setting = ClinicSetting::getInstance();

        return response()->json([
            'success' => true,
            'message' => 'Clinic settings retrieved successfully',
            'data' => $setting,
        ]);
    }

    /**
     * Update clinic settings
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Basic Information
            'name' => 'required|string|max:100',
            'tagline' => 'nullable|string|max:200',
            'description' => 'nullable|string|max:1000',

            // Contact Information
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|url|max:200',

            // Social Media
            'facebook' => 'nullable|string|max:200',
            'instagram' => 'nullable|string|max:200',
            'twitter' => 'nullable|string|max:200',

            // Legal Information
            'license_number' => 'nullable|string|max:100',
            'npwp' => 'nullable|string|max:50',
            'owner_name' => 'nullable|string|max:100',

            // Operational Hours
            'operational_hours' => 'nullable|array',
            'operational_hours.*.open' => 'required_with:operational_hours|string',
            'operational_hours.*.close' => 'required_with:operational_hours|string',
            'operational_hours.*.is_open' => 'required_with:operational_hours|boolean',

            // Settings
            'timezone' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:10',
            'date_format' => 'nullable|string|max:20',
            'time_format' => 'nullable|string|max:10',

            // Queue Settings
            'default_queue_quota' => 'nullable|integer|min:1|max:500',
            'appointment_duration' => 'nullable|integer|min:5|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $setting = ClinicSetting::getInstance();
        $setting->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Clinic settings updated successfully',
            'data' => $setting->fresh(),
        ]);
    }

    /**
     * Upload logo
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $setting = ClinicSetting::getInstance();

        // Delete old logo if exists
        if ($setting->logo && Storage::disk('public')->exists($setting->logo)) {
            Storage::disk('public')->delete($setting->logo);
        }

        // Store new logo
        $path = $request->file('logo')->store('clinic', 'public');
        $setting->update(['logo' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Logo uploaded successfully',
            'data' => [
                'logo' => $path,
                'url' => Storage::disk('public')->url($path),
            ],
        ]);
    }

    /**
     * Upload favicon
     */
    public function uploadFavicon(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'favicon' => 'required|image|mimes:ico,png|max:512',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $setting = ClinicSetting::getInstance();

        // Delete old favicon if exists
        if ($setting->favicon && Storage::disk('public')->exists($setting->favicon)) {
            Storage::disk('public')->delete($setting->favicon);
        }

        // Store new favicon
        $path = $request->file('favicon')->store('clinic', 'public');
        $setting->update(['favicon' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Favicon uploaded successfully',
            'data' => [
                'favicon' => $path,
                'url' => Storage::disk('public')->url($path),
            ],
        ]);
    }

    /**
     * Get public clinic info (for display without auth)
     */
    public function publicInfo(): JsonResponse
    {
        $setting = ClinicSetting::getInstance();

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $setting->name,
                'tagline' => $setting->tagline,
                'logo' => $setting->logo ? Storage::disk('public')->url($setting->logo) : null,
                'address' => $setting->full_address,
                'phone' => $setting->phone,
                'whatsapp' => $setting->whatsapp,
                'email' => $setting->email,
                'operational_hours' => $setting->operational_hours,
            ],
        ]);
    }

    /**
     * Get timezone options
     */
    public function timezones(): JsonResponse
    {
        $timezones = [
            'Asia/Jakarta' => 'WIB - Jakarta',
            'Asia/Makassar' => 'WITA - Makassar',
            'Asia/Jayapura' => 'WIT - Jayapura',
        ];

        return response()->json([
            'success' => true,
            'data' => $timezones,
        ]);
    }
}
