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
        Schema::create("medical_record_services", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("medical_record_id")
                ->constrained("medical_records")
                ->onDelete("cascade");
            $table
                ->foreignId("service_id")
                ->nullable()
                ->constrained("services");
            $table->string("service_name");
            $table->integer("quantity")->default(1);
            $table->decimal("unit_price", 12, 2)->default(0);
            $table->decimal("total_price", 12, 2)->default(0);
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
        Schema::dropIfExists("medical_record_services");
    }
};
