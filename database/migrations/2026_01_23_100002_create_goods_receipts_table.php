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
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 30)->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
            $table->date('receipt_date');

            // Supplier document reference
            $table->string('supplier_invoice_number')->nullable();
            $table->date('supplier_invoice_date')->nullable();

            // Status: draft, completed, cancelled
            $table->enum('status', ['draft', 'completed', 'cancelled'])->default('draft');

            // Totals
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->foreignId('received_by')->constrained('users')->onDelete('restrict');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'receipt_date']);
            $table->index(['supplier_id', 'receipt_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
