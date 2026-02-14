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
        Schema::create("medical_records", function (Blueprint $table) {
            $table->id();
            $table->string("record_number", 20)->unique();
            $table->foreignId("queue_id")->constrained("queues");
            $table->foreignId("patient_id")->constrained("patients");
            $table->foreignId("department_id")->constrained("departments");
            $table->foreignId("doctor_id")->constrained("users");
            $table->date("visit_date");

            // Anamnesis (Keluhan)
            $table->text("chief_complaint")->nullable();
            $table->text("present_illness")->nullable();
            $table->text("past_medical_history")->nullable();
            $table->text("family_history")->nullable();
            $table->text("allergy_notes")->nullable();

            // Pemeriksaan Fisik (Vital Signs)
            $table->integer("blood_pressure_systolic")->nullable();
            $table->integer("blood_pressure_diastolic")->nullable();
            $table->integer("heart_rate")->nullable();
            $table->integer("respiratory_rate")->nullable();
            $table->decimal("temperature", 4, 1)->nullable();
            $table->decimal("weight", 5, 2)->nullable();
            $table->decimal("height", 5, 2)->nullable();
            $table->integer("oxygen_saturation")->nullable();
            $table->text("physical_examination")->nullable();

            // Diagnosis
            $table->text("diagnosis")->nullable();
            $table->text("diagnosis_notes")->nullable();

            // Tindakan & Treatment
            $table->text("treatment")->nullable();
            $table->text("treatment_notes")->nullable();

            // Anjuran
            $table->text("recommendations")->nullable();
            $table->date("follow_up_date")->nullable();

            // Status
            $table
                ->enum("status", ["in_progress", "completed", "cancelled"])
                ->default("in_progress");
            $table->timestamp("completed_at")->nullable();

            $table->timestamps();

            $table->index(["patient_id", "visit_date"]);
            $table->index(["department_id", "visit_date"]);
            $table->index(["doctor_id", "visit_date"]);
            $table->index("status");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("medical_records");
    }
};
