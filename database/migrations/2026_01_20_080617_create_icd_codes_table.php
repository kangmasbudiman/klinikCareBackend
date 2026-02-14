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
        Schema::create("icd_codes", function (Blueprint $table) {
            $table->id();

            // Kode ICD (unique per type)
            $table->string("code", 20);

            // Type: icd10 (diagnosis) atau icd9cm (prosedur)
            $table->enum("type", ["icd10", "icd9cm"])->default("icd10");

            // Nama dalam 2 bahasa
            $table->string("name_id", 500); // Bahasa Indonesia
            $table->string("name_en", 500)->nullable(); // English

            // Kategori/Chapter untuk grouping
            $table->string("chapter", 10)->nullable(); // Misal: I, II, III atau 01, 02
            $table->string("chapter_name", 255)->nullable(); // Nama chapter

            // Block untuk sub-grouping (misal: A00-A09)
            $table->string("block", 20)->nullable();
            $table->string("block_name", 255)->nullable();

            // Hierarki - untuk parent-child relationship
            // Misal: A00 adalah parent dari A00.0, A00.1, dst
            $table->string("parent_code", 20)->nullable();

            // BPJS/INA-CBGs related
            $table->string("dtd_code", 20)->nullable(); // Kode Daftar Tindakan Dokter
            $table->boolean("is_bpjs_claimable")->default(true); // Bisa diklaim BPJS

            // Status dan notes
            $table->boolean("is_active")->default(true);
            $table->text("notes")->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(["code", "type"]); // Kombinasi code + type harus unique
            $table->index("type");
            $table->index("chapter");
            $table->index("block");
            $table->index("parent_code");
            $table->index("is_active");
            $table->index("is_bpjs_claimable");

            // Full text search untuk nama
            $table->fullText(["code", "name_id", "name_en"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("icd_codes");
    }
};
