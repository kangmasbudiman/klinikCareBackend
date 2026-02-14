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
        Schema::create("medicine_batches", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("medicine_id")
                ->constrained("medicines")
                ->cascadeOnDelete();
            $table->string("batch_number", 50);
            $table->date("expiry_date");

            // Qty & Harga per Batch
            $table->integer("initial_qty"); // Qty awal saat terima
            $table->integer("current_qty"); // Qty sisa saat ini
            $table->decimal("purchase_price", 12, 2)->nullable(); // Harga beli batch ini

            // Referensi penerimaan
            $table->foreignId("purchase_receipt_item_id")->nullable();

            // Status
            $table
                ->enum("status", ["available", "low", "expired", "empty"])
                ->default("available");

            $table->timestamps();

            $table->index(["medicine_id", "expiry_date"]);
            $table->index("batch_number");
            $table->index("status");
            $table->index("expiry_date");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("medicine_batches");
    }
};
