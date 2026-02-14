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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_number', 30)->unique();
            $table->foreignId('medicine_id')->constrained()->onDelete('restrict');
            $table->foreignId('medicine_batch_id')->nullable()->constrained()->onDelete('set null');

            // Movement type: in (masuk), out (keluar)
            $table->enum('movement_type', ['in', 'out']);

            // Reason for movement
            $table->enum('reason', [
                'purchase',           // Pembelian/penerimaan dari supplier
                'sales',              // Penjualan/penyerahan ke pasien
                'adjustment_plus',    // Penyesuaian stok tambah
                'adjustment_minus',   // Penyesuaian stok kurang
                'return_supplier',    // Retur ke supplier
                'return_patient',     // Retur dari pasien
                'expired',            // Kadaluarsa/pemusnahan
                'damage',             // Rusak
                'transfer_in',        // Mutasi masuk dari cabang lain
                'transfer_out',       // Mutasi keluar ke cabang lain
                'initial_stock',      // Stok awal
                'other'               // Lainnya
            ]);

            $table->integer('quantity');
            $table->string('unit', 50);

            // Stock tracking
            $table->integer('stock_before');
            $table->integer('stock_after');

            // Reference to related documents
            $table->string('reference_type')->nullable(); // goods_receipt, prescription, adjustment, etc.
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->text('notes')->nullable();
            $table->timestamp('movement_date');
            $table->timestamps();

            $table->index(['medicine_id', 'movement_date']);
            $table->index(['medicine_batch_id', 'movement_date']);
            $table->index(['movement_type', 'reason']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
