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
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_item_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('medicine_id')->constrained()->onDelete('restrict');
            $table->foreignId('medicine_batch_id')->nullable()->constrained()->onDelete('set null');

            $table->integer('quantity');
            $table->string('unit', 50);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);

            // Batch info (will create new batch when goods receipt completed)
            $table->string('batch_number', 50);
            $table->date('expiry_date');

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['goods_receipt_id', 'medicine_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
