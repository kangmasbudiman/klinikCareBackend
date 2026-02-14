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
        Schema::create("services", function (Blueprint $table) {
            $table->id();
            $table->string("code", 20)->unique();
            $table->string("name", 100);
            $table->text("description")->nullable();

            // Category: konsultasi, tindakan, laboratorium, radiologi, farmasi, dll
            $table->string("category", 50);

            // Relasi ke departemen (opsional)
            $table
                ->foreignId("department_id")
                ->nullable()
                ->constrained("departments")
                ->nullOnDelete();

            // Pricing
            $table->decimal("base_price", 12, 2)->default(0);
            $table->decimal("doctor_fee", 12, 2)->default(0);
            $table->decimal("hospital_fee", 12, 2)->default(0);

            // Duration in minutes (for scheduling)
            $table->integer("duration")->default(15);

            // Status
            $table->boolean("is_active")->default(true);
            $table->boolean("requires_appointment")->default(false);

            // Additional info
            $table->string("icon", 50)->nullable();
            $table->string("color", 20)->nullable();

            $table->timestamps();

            // Indexes
            $table->index("category");
            $table->index("is_active");
            $table->index("department_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("services");
    }
};
