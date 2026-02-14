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
        Schema::create("doctor_schedules", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("doctor_id")
                ->constrained("users")
                ->onDelete("cascade");
            $table
                ->foreignId("department_id")
                ->constrained("departments")
                ->onDelete("cascade");
            $table
                ->tinyInteger("day_of_week")
                ->comment("0=Minggu, 1=Senin, ..., 6=Sabtu");
            $table->time("start_time");
            $table->time("end_time");
            $table->integer("quota")->default(20);
            $table->boolean("is_active")->default(true);
            $table->text("notes")->nullable();
            $table->timestamps();

            // Indexes
            $table->index(["doctor_id", "day_of_week"]);
            $table->index(["department_id", "day_of_week"]);
            $table->index("is_active");

            // Unique constraint: satu dokter tidak bisa punya 2 jadwal di hari & jam yang sama
            $table->unique(
                ["doctor_id", "department_id", "day_of_week", "start_time"],
                "unique_doctor_schedule",
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("doctor_schedules");
    }
};
