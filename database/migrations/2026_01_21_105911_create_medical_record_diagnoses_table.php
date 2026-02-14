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
        Schema::create("medical_record_diagnoses", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("medical_record_id")
                ->constrained("medical_records")
                ->onDelete("cascade");
            $table->string("icd_code", 20)->nullable();
            $table->string("icd_name")->nullable();
            $table
                ->enum("diagnosis_type", ["primary", "secondary"])
                ->default("primary");
            $table->text("notes")->nullable();
            $table->timestamps();

            $table->index("medical_record_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("medical_record_diagnoses");
    }
};
