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
        Schema::table("medicines", function (Blueprint $table) {
            // Margin dan PPN
            $table
                ->decimal("margin_percentage", 5, 2)
                ->default(0)
                ->after("purchase_price");
            $table
                ->decimal("ppn_percentage", 5, 2)
                ->default(11)
                ->after("margin_percentage");
            $table
                ->boolean("is_ppn_included")
                ->default(false)
                ->after("ppn_percentage");

            // Harga sebelum PPN (untuk kalkulasi)
            $table
                ->decimal("price_before_ppn", 12, 2)
                ->default(0)
                ->after("is_ppn_included");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("medicines", function (Blueprint $table) {
            $table->dropColumn([
                "margin_percentage",
                "ppn_percentage",
                "is_ppn_included",
                "price_before_ppn",
            ]);
        });
    }
};
