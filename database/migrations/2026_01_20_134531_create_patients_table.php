<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("patients", function (Blueprint $table) {
            $table->id();
            $table->string("medical_record_number", 20)->unique(); // No. RM auto-generate
            $table->string("nik", 16)->unique()->nullable(); // NIK KTP
            $table->string("bpjs_number", 13)->nullable(); // No. BPJS
            $table->string("name"); // Nama lengkap
            $table->string("birth_place")->nullable();
            $table->date("birth_date");
            $table->enum("gender", ["male", "female"]);
            $table->enum("blood_type", ["A", "B", "AB", "O"])->nullable();
            $table
                ->enum("religion", [
                    "islam",
                    "kristen",
                    "katolik",
                    "hindu",
                    "buddha",
                    "konghucu",
                    "lainnya",
                ])
                ->nullable();
            $table
                ->enum("marital_status", [
                    "single",
                    "married",
                    "divorced",
                    "widowed",
                ])
                ->nullable();
            $table->string("occupation")->nullable(); // Pekerjaan
            $table->string("education")->nullable(); // Pendidikan terakhir
            $table->string("phone", 20)->nullable();
            $table->string("email")->nullable();
            $table->text("address")->nullable();
            $table->string("rt", 5)->nullable();
            $table->string("rw", 5)->nullable();
            $table->string("village")->nullable(); // Kelurahan
            $table->string("district")->nullable(); // Kecamatan
            $table->string("city")->nullable(); // Kota/Kabupaten
            $table->string("province")->nullable();
            $table->string("postal_code", 10)->nullable();
            $table->string("emergency_contact_name")->nullable();
            $table->string("emergency_contact_relation")->nullable();
            $table->string("emergency_contact_phone", 20)->nullable();
            $table->text("allergies")->nullable(); // Alergi
            $table->text("medical_notes")->nullable(); // Catatan medis
            $table
                ->enum("patient_type", ["umum", "bpjs", "asuransi"])
                ->default("umum");
            $table->string("insurance_name")->nullable(); // Nama asuransi jika asuransi
            $table->string("insurance_number")->nullable(); // No. polis
            $table->string("photo")->nullable(); // Path foto pasien
            $table->string("satusehat_id")->nullable(); // ID SatuSehat (untuk integrasi)
            $table->boolean("is_active")->default(true);
            $table->timestamps();

            // Indexes for search
            $table->index("name");
            $table->index("bpjs_number");
            $table->index("patient_type");
            $table->index("is_active");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("patients");
    }
};
