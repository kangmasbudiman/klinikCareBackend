<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Department;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get departments for relation
        $departments = Department::pluck('id', 'code')->toArray();

        $services = [
            // Konsultasi
            [
                'code' => 'KON-001',
                'name' => 'Konsultasi Dokter Umum',
                'description' => 'Konsultasi dengan dokter umum untuk keluhan kesehatan',
                'category' => 'konsultasi',
                'department_code' => 'POLI-001',
                'base_price' => 50000,
                'doctor_fee' => 30000,
                'hospital_fee' => 20000,
                'duration' => 15,
                'icon' => 'stethoscope',
                'color' => 'blue',
            ],
            [
                'code' => 'KON-002',
                'name' => 'Konsultasi Dokter Gigi',
                'description' => 'Konsultasi dengan dokter gigi untuk masalah gigi dan mulut',
                'category' => 'konsultasi',
                'department_code' => 'POLI-002',
                'base_price' => 75000,
                'doctor_fee' => 50000,
                'hospital_fee' => 25000,
                'duration' => 20,
                'icon' => 'stethoscope',
                'color' => 'cyan',
            ],
            [
                'code' => 'KON-003',
                'name' => 'Konsultasi Dokter Anak',
                'description' => 'Konsultasi dengan dokter spesialis anak',
                'category' => 'konsultasi',
                'department_code' => 'POLI-003',
                'base_price' => 100000,
                'doctor_fee' => 70000,
                'hospital_fee' => 30000,
                'duration' => 20,
                'icon' => 'stethoscope',
                'color' => 'pink',
            ],
            [
                'code' => 'KON-004',
                'name' => 'Konsultasi Dokter Kandungan',
                'description' => 'Konsultasi dengan dokter spesialis kandungan',
                'category' => 'konsultasi',
                'department_code' => 'POLI-004',
                'base_price' => 150000,
                'doctor_fee' => 100000,
                'hospital_fee' => 50000,
                'duration' => 30,
                'icon' => 'stethoscope',
                'color' => 'purple',
                'requires_appointment' => true,
            ],

            // Tindakan Medis
            [
                'code' => 'TIN-001',
                'name' => 'Cabut Gigi',
                'description' => 'Tindakan pencabutan gigi',
                'category' => 'tindakan',
                'department_code' => 'POLI-002',
                'base_price' => 150000,
                'doctor_fee' => 100000,
                'hospital_fee' => 50000,
                'duration' => 30,
                'icon' => 'scissors',
                'color' => 'red',
            ],
            [
                'code' => 'TIN-002',
                'name' => 'Tambal Gigi',
                'description' => 'Tindakan penambalan gigi berlubang',
                'category' => 'tindakan',
                'department_code' => 'POLI-002',
                'base_price' => 200000,
                'doctor_fee' => 120000,
                'hospital_fee' => 80000,
                'duration' => 45,
                'icon' => 'scissors',
                'color' => 'cyan',
            ],
            [
                'code' => 'TIN-003',
                'name' => 'Jahit Luka',
                'description' => 'Tindakan penjahitan luka',
                'category' => 'tindakan',
                'department_code' => 'POLI-001',
                'base_price' => 100000,
                'doctor_fee' => 60000,
                'hospital_fee' => 40000,
                'duration' => 30,
                'icon' => 'scissors',
                'color' => 'red',
            ],
            [
                'code' => 'TIN-004',
                'name' => 'Suntik/Injeksi',
                'description' => 'Tindakan pemberian obat melalui suntikan',
                'category' => 'tindakan',
                'department_code' => 'POLI-001',
                'base_price' => 25000,
                'doctor_fee' => 0,
                'hospital_fee' => 25000,
                'duration' => 10,
                'icon' => 'syringe',
                'color' => 'blue',
            ],
            [
                'code' => 'TIN-005',
                'name' => 'Pemasangan Infus',
                'description' => 'Tindakan pemasangan infus',
                'category' => 'tindakan',
                'department_code' => 'POLI-001',
                'base_price' => 75000,
                'doctor_fee' => 0,
                'hospital_fee' => 75000,
                'duration' => 15,
                'icon' => 'droplet',
                'color' => 'teal',
            ],
            [
                'code' => 'TIN-006',
                'name' => 'Nebulizer',
                'description' => 'Terapi uap untuk gangguan pernapasan',
                'category' => 'tindakan',
                'department_code' => 'POLI-001',
                'base_price' => 50000,
                'doctor_fee' => 0,
                'hospital_fee' => 50000,
                'duration' => 20,
                'icon' => 'activity',
                'color' => 'green',
            ],

            // Laboratorium
            [
                'code' => 'LAB-001',
                'name' => 'Cek Darah Lengkap',
                'description' => 'Pemeriksaan darah lengkap (CBC)',
                'category' => 'laboratorium',
                'department_code' => 'LAB-001',
                'base_price' => 150000,
                'doctor_fee' => 0,
                'hospital_fee' => 150000,
                'duration' => 60,
                'icon' => 'droplet',
                'color' => 'red',
            ],
            [
                'code' => 'LAB-002',
                'name' => 'Cek Gula Darah',
                'description' => 'Pemeriksaan kadar gula dalam darah',
                'category' => 'laboratorium',
                'department_code' => 'LAB-001',
                'base_price' => 35000,
                'doctor_fee' => 0,
                'hospital_fee' => 35000,
                'duration' => 15,
                'icon' => 'droplet',
                'color' => 'orange',
            ],
            [
                'code' => 'LAB-003',
                'name' => 'Cek Kolesterol',
                'description' => 'Pemeriksaan kadar kolesterol',
                'category' => 'laboratorium',
                'department_code' => 'LAB-001',
                'base_price' => 50000,
                'doctor_fee' => 0,
                'hospital_fee' => 50000,
                'duration' => 15,
                'icon' => 'droplet',
                'color' => 'yellow',
            ],
            [
                'code' => 'LAB-004',
                'name' => 'Cek Asam Urat',
                'description' => 'Pemeriksaan kadar asam urat',
                'category' => 'laboratorium',
                'department_code' => 'LAB-001',
                'base_price' => 40000,
                'doctor_fee' => 0,
                'hospital_fee' => 40000,
                'duration' => 15,
                'icon' => 'droplet',
                'color' => 'purple',
            ],
            [
                'code' => 'LAB-005',
                'name' => 'Urinalisis',
                'description' => 'Pemeriksaan urine lengkap',
                'category' => 'laboratorium',
                'department_code' => 'LAB-001',
                'base_price' => 50000,
                'doctor_fee' => 0,
                'hospital_fee' => 50000,
                'duration' => 30,
                'icon' => 'microscope',
                'color' => 'indigo',
            ],
            [
                'code' => 'LAB-006',
                'name' => 'Tes Kehamilan',
                'description' => 'Pemeriksaan tes kehamilan (HCG)',
                'category' => 'laboratorium',
                'department_code' => 'LAB-001',
                'base_price' => 75000,
                'doctor_fee' => 0,
                'hospital_fee' => 75000,
                'duration' => 30,
                'icon' => 'microscope',
                'color' => 'pink',
            ],

            // Radiologi
            [
                'code' => 'RAD-001',
                'name' => 'Rontgen Dada',
                'description' => 'Pemeriksaan rontgen dada (thorax)',
                'category' => 'radiologi',
                'department_code' => 'RAD-001',
                'base_price' => 150000,
                'doctor_fee' => 50000,
                'hospital_fee' => 100000,
                'duration' => 30,
                'icon' => 'scan',
                'color' => 'blue',
            ],
            [
                'code' => 'RAD-002',
                'name' => 'USG Kandungan',
                'description' => 'Pemeriksaan USG untuk ibu hamil',
                'category' => 'radiologi',
                'department_code' => 'RAD-001',
                'base_price' => 250000,
                'doctor_fee' => 100000,
                'hospital_fee' => 150000,
                'duration' => 30,
                'icon' => 'scan',
                'color' => 'purple',
                'requires_appointment' => true,
            ],
            [
                'code' => 'RAD-003',
                'name' => 'USG Abdomen',
                'description' => 'Pemeriksaan USG perut',
                'category' => 'radiologi',
                'department_code' => 'RAD-001',
                'base_price' => 200000,
                'doctor_fee' => 75000,
                'hospital_fee' => 125000,
                'duration' => 30,
                'icon' => 'scan',
                'color' => 'teal',
            ],
            [
                'code' => 'RAD-004',
                'name' => 'EKG',
                'description' => 'Pemeriksaan rekam jantung (Elektrokardiogram)',
                'category' => 'radiologi',
                'department_code' => 'RAD-001',
                'base_price' => 100000,
                'doctor_fee' => 50000,
                'hospital_fee' => 50000,
                'duration' => 20,
                'icon' => 'heart-pulse',
                'color' => 'red',
            ],

            // Lainnya
            [
                'code' => 'LAN-001',
                'name' => 'Surat Keterangan Sehat',
                'description' => 'Pembuatan surat keterangan sehat',
                'category' => 'lainnya',
                'department_code' => 'POLI-001',
                'base_price' => 50000,
                'doctor_fee' => 25000,
                'hospital_fee' => 25000,
                'duration' => 15,
                'icon' => 'clipboard',
                'color' => 'green',
            ],
            [
                'code' => 'LAN-002',
                'name' => 'Medical Check Up Dasar',
                'description' => 'Paket pemeriksaan kesehatan dasar',
                'category' => 'lainnya',
                'department_code' => 'POLI-001',
                'base_price' => 500000,
                'doctor_fee' => 150000,
                'hospital_fee' => 350000,
                'duration' => 120,
                'icon' => 'clipboard',
                'color' => 'indigo',
                'requires_appointment' => true,
            ],
        ];

        foreach ($services as $serviceData) {
            $departmentCode = $serviceData['department_code'] ?? null;
            unset($serviceData['department_code']);

            // Set department_id from code
            if ($departmentCode && isset($departments[$departmentCode])) {
                $serviceData['department_id'] = $departments[$departmentCode];
            }

            // Set default values
            $serviceData['is_active'] = $serviceData['is_active'] ?? true;
            $serviceData['requires_appointment'] = $serviceData['requires_appointment'] ?? false;

            Service::create($serviceData);
        }
    }
}
