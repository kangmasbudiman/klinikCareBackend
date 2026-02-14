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
        Schema::create('clinic_settings', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('name', 100);
            $table->string('tagline', 200)->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();

            // Contact Information
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('phone_2', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('website', 200)->nullable();

            // Social Media
            $table->string('facebook', 200)->nullable();
            $table->string('instagram', 200)->nullable();
            $table->string('twitter', 200)->nullable();

            // Legal Information
            $table->string('license_number', 100)->nullable(); // Nomor izin klinik
            $table->string('npwp', 50)->nullable();
            $table->string('owner_name', 100)->nullable();

            // Operational Hours (JSON format)
            $table->json('operational_hours')->nullable();

            // Settings
            $table->string('timezone', 50)->default('Asia/Jakarta');
            $table->string('currency', 10)->default('IDR');
            $table->string('date_format', 20)->default('d/m/Y');
            $table->string('time_format', 10)->default('H:i');

            // Queue Settings
            $table->integer('default_queue_quota')->default(50);
            $table->integer('appointment_duration')->default(15); // minutes

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_settings');
    }
};
