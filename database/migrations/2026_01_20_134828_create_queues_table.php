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
        Schema::create("queues", function (Blueprint $table) {
            $table->id();
            $table->integer("queue_number"); // Nomor antrian
            $table->string("queue_code", 20); // Kode antrian (A-001, B-002)
            $table->date("queue_date"); // Tanggal antrian
            $table
                ->foreignId("patient_id")
                ->nullable()
                ->constrained()
                ->nullOnDelete(); // Null jika belum terdaftar
            $table
                ->foreignId("department_id")
                ->constrained()
                ->cascadeOnDelete(); // Poli tujuan
            $table
                ->foreignId("service_id")
                ->nullable()
                ->constrained()
                ->nullOnDelete(); // Layanan yang dipilih
            $table
                ->enum("status", [
                    "waiting",
                    "called",
                    "in_service",
                    "completed",
                    "skipped",
                    "cancelled",
                ])
                ->default("waiting");
            $table->timestamp("called_at")->nullable(); // Waktu dipanggil
            $table->timestamp("started_at")->nullable(); // Waktu mulai dilayani
            $table->timestamp("completed_at")->nullable(); // Waktu selesai
            $table->integer("counter_number")->nullable(); // Nomor loket/ruangan
            $table
                ->foreignId("served_by")
                ->nullable()
                ->constrained("users")
                ->nullOnDelete(); // Petugas yang melayani
            $table->text("notes")->nullable();
            $table->timestamps();

            // Indexes
            $table->index(["queue_date", "department_id"]);
            $table->index(["queue_date", "status"]);
            $table->unique(["queue_date", "queue_code"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("queues");
    }
};
