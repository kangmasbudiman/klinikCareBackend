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
        Schema::create("queue_settings", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("department_id")
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->string("prefix", 5); // Prefix kode antrian (A, B, C)
            $table->integer("daily_quota")->default(50);
            $table->integer("start_number")->default(1);
            $table->boolean("is_active")->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("queue_settings");
    }
};
