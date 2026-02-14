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
        Schema::create("medicines", function (Blueprint $table) {
            $table->id();
            $table->string("code", 20)->unique();
            $table->string("name", 255);
            $table->string("generic_name", 255)->nullable();
            $table
                ->foreignId("category_id")
                ->nullable()
                ->constrained("medicine_categories")
                ->nullOnDelete();

            // Satuan
            $table->string("unit", 50); // Tablet, Kapsul, Botol, Tube, dll
            $table->integer("unit_conversion")->default(1); // Konversi ke satuan terkecil

            // Harga
            $table->decimal("purchase_price", 12, 2)->default(0); // Harga beli terakhir
            $table->decimal("selling_price", 12, 2)->default(0); // Harga jual

            // Stok
            $table->integer("min_stock")->default(10); // Stok minimum (reorder point)
            $table->integer("max_stock")->default(100); // Stok maksimum

            // Info Tambahan
            $table->string("manufacturer", 100)->nullable(); // Pabrik/Produsen
            $table->text("description")->nullable();
            $table->boolean("requires_prescription")->default(false); // Perlu resep dokter
            $table->boolean("is_active")->default(true);

            $table->timestamps();

            $table->index("name");
            $table->index("generic_name");
            $table->index("category_id");
            $table->index("is_active");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("medicines");
    }
};
