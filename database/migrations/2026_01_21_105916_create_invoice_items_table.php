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
        Schema::create("invoice_items", function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId("invoice_id")
                ->constrained("invoices")
                ->onDelete("cascade");
            $table
                ->enum("item_type", ["service", "medicine", "other"])
                ->default("service");
            $table->string("item_name");
            $table->integer("quantity")->default(1);
            $table->decimal("unit_price", 12, 2)->default(0);
            $table->decimal("total_price", 12, 2)->default(0);
            $table->text("notes")->nullable();
            $table->timestamps();

            $table->index("invoice_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("invoice_items");
    }
};
