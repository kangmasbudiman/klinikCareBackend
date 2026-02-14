<?php

namespace Database\Seeders;

use App\Models\ClinicSetting;
use Illuminate\Database\Seeder;

class ClinicSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ClinicSetting::create([
            // Basic Information
            'name' => 'KlinikCare',
            'tagline' => 'Kesehatan Anda, Prioritas Kami',
            'description' => 'KlinikCare adalah klinik kesehatan modern yang menyediakan layanan kesehatan berkualitas dengan didukung oleh tenaga medis profesional dan peralatan medis terkini.',

            // Contact Information
            'address' => 'Jl. Kesehatan No. 123',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'postal_code' => '12345',
            'phone' => '(021) 1234-5678',
            'phone_2' => '(021) 8765-4321',
            'whatsapp' => '081234567890',
            'email' => 'info@klinikcare.com',
            'website' => 'https://klinikcare.com',

            // Social Media
            'facebook' => 'https://facebook.com/klinikcare',
            'instagram' => 'https://instagram.com/klinikcare',
            'twitter' => 'https://twitter.com/klinikcare',

            // Legal Information
            'license_number' => '503/KLINIK/DKI/2024',
            'npwp' => '12.345.678.9-012.000',
            'owner_name' => 'dr. Ahmad Sehat, Sp.PD',

            // Operational Hours
            'operational_hours' => [
                'monday' => ['open' => '08:00', 'close' => '21:00', 'is_open' => true],
                'tuesday' => ['open' => '08:00', 'close' => '21:00', 'is_open' => true],
                'wednesday' => ['open' => '08:00', 'close' => '21:00', 'is_open' => true],
                'thursday' => ['open' => '08:00', 'close' => '21:00', 'is_open' => true],
                'friday' => ['open' => '08:00', 'close' => '21:00', 'is_open' => true],
                'saturday' => ['open' => '08:00', 'close' => '17:00', 'is_open' => true],
                'sunday' => ['open' => '08:00', 'close' => '12:00', 'is_open' => false],
            ],

            // Settings
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',

            // Queue Settings
            'default_queue_quota' => 50,
            'appointment_duration' => 15,
        ]);
    }
}
