<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medical_letters', function (Blueprint $table) {
            $table->id();
            $table->string('letter_number')->unique();
            $table->enum('letter_type', [
                'surat_sehat',
                'surat_sakit',
                'surat_rujukan',
                'surat_keterangan',
            ]);
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('doctor_id')->constrained('users');
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->nullOnDelete();

            // Common fields
            $table->date('letter_date');
            $table->text('purpose')->nullable();
            $table->text('notes')->nullable();

            // Surat Sakit specific
            $table->date('sick_start_date')->nullable();
            $table->date('sick_end_date')->nullable();
            $table->integer('sick_days')->nullable();

            // Surat Rujukan specific
            $table->string('referral_destination')->nullable();
            $table->string('referral_specialist')->nullable();
            $table->text('referral_reason')->nullable();
            $table->text('diagnosis_summary')->nullable();
            $table->text('treatment_summary')->nullable();

            // Surat Sehat specific
            $table->string('health_purpose')->nullable();
            $table->text('examination_result')->nullable();

            // Surat Keterangan specific
            $table->text('statement_content')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['patient_id', 'letter_type']);
            $table->index('letter_date');
            $table->index('doctor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_letters');
    }
};
