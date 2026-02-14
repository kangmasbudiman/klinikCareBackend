<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                "name" => "Super Admin",
                "email" => "admin@klinik.com",
                "password" => Hash::make("password"),
                "role" => User::ROLE_SUPER_ADMIN,
                "phone" => "081234567890",
                "is_active" => true,
                "avatar" =>
                    "https://api.dicebear.com/7.x/avataaars/svg?seed=SuperAdmin",
            ],
            [
                "name" => "Admin Klinik",
                "email" => "adminklinik@klinik.com",
                "password" => Hash::make("password"),
                "role" => User::ROLE_ADMIN_KLINIK,
                "phone" => "081234567891",
                "is_active" => true,
                "avatar" =>
                    "https://api.dicebear.com/7.x/avataaars/svg?seed=AdminKlinik",
            ],
            [
                "name" => "Dr. Ahmad Wijaya",
                "email" => "dokter@klinik.com",
                "password" => Hash::make("password"),
                "role" => User::ROLE_DOKTER,
                "phone" => "081234567892",
                "is_active" => true,
                "avatar" =>
                    "https://api.dicebear.com/7.x/avataaars/svg?seed=Ahmad",
            ],
            [
                "name" => "Siti Rahayu",
                "email" => "perawat@klinik.com",
                "password" => Hash::make("password"),
                "role" => User::ROLE_PERAWAT,
                "phone" => "081234567893",
                "is_active" => true,
                "avatar" =>
                    "https://api.dicebear.com/7.x/avataaars/svg?seed=Siti",
            ],
            [
                "name" => "Budi Santoso",
                "email" => "kasir@klinik.com",
                "password" => Hash::make("password"),
                "role" => User::ROLE_KASIR,
                "phone" => "081234567894",
                "is_active" => true,
                "avatar" =>
                    "https://api.dicebear.com/7.x/avataaars/svg?seed=Budi",
            ],
            [
                "name" => "Dewi Lestari",
                "email" => "pasien@klinik.com",
                "password" => Hash::make("password"),
                "role" => User::ROLE_PASIEN,
                "phone" => "081234567895",
                "is_active" => true,
                "avatar" =>
                    "https://api.dicebear.com/7.x/avataaars/svg?seed=Dewi",
            ],
            [
                "name" => "Dr. Maya Putri",
                "email" => "maya.putri@klinik.com",
                "password" => Hash::make("password"),
                "role" => User::ROLE_DOKTER,
                "phone" => "081234567896",
                "is_active" => true,
                "avatar" =>
                    "https://api.dicebear.com/7.x/avataaars/svg?seed=Maya",
            ],
            [
                "name" => "Rina Susanti",
                "email" => "rina.susanti@klinik.com",
                "password" => Hash::make("password"),
                "role" => User::ROLE_PERAWAT,
                "phone" => "081234567897",
                "is_active" => true,
                "avatar" =>
                    "https://api.dicebear.com/7.x/avataaars/svg?seed=Rina",
            ],
            [
                "name" => "Hendra Pratama",
                "email" => "hendra.pratama@klinik.com",
                "password" => Hash::make("password"),
                "role" => User::ROLE_PASIEN,
                "phone" => "081234567898",
                "is_active" => false,
                "avatar" =>
                    "https://api.dicebear.com/7.x/avataaars/svg?seed=Hendra",
            ],
            [
                "name" => "Linda Wijayanti",
                "email" => "linda.wijayanti@klinik.com",
                "password" => Hash::make("password"),
                "role" => User::ROLE_KASIR,
                "phone" => "081234567899",
                "is_active" => true,
                "avatar" =>
                    "https://api.dicebear.com/7.x/avataaars/svg?seed=Linda",
            ],
            [
                "name" => "Apt. Sri Handayani",
                "email" => "apoteker@klinik.com",
                "password" => Hash::make("password"),
                "role" => User::ROLE_APOTEKER,
                "phone" => "081234567900",
                "is_active" => true,
                "avatar" =>
                    "https://api.dicebear.com/7.x/avataaars/svg?seed=Sri",
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(["email" => $userData["email"]], $userData);
        }
    }
}
