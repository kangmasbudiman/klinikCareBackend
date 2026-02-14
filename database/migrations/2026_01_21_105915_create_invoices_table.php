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
        Schema::create("invoices", function (Blueprint $table) {
            $table->id();
            $table->string("invoice_number", 20)->unique();
            $table
                ->foreignId("medical_record_id")
                ->constrained("medical_records");
            $table->foreignId("patient_id")->constrained("patients");

            // Rincian Biaya
            $table->decimal("subtotal", 12, 2)->default(0);
            $table->decimal("discount_amount", 12, 2)->default(0);
            $table->decimal("discount_percent", 5, 2)->default(0);
            $table->decimal("tax_amount", 12, 2)->default(0);
            $table->decimal("total_amount", 12, 2)->default(0);
            $table->decimal("paid_amount", 12, 2)->default(0);
            $table->decimal("change_amount", 12, 2)->default(0);

            // Pembayaran
            $table
                ->enum("payment_method", [
                    "cash",
                    "card",
                    "transfer",
                    "bpjs",
                    "insurance",
                ])
                ->default("cash");
            $table
                ->enum("payment_status", ["unpaid", "partial", "paid"])
                ->default("unpaid");
            $table->timestamp("payment_date")->nullable();

            // Info Tambahan
            $table->text("notes")->nullable();
            $table->foreignId("cashier_id")->nullable()->constrained("users");

            $table->timestamps();

            $table->index("patient_id");
            $table->index("payment_status");
            $table->index("created_at");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("invoices");
    }
};
