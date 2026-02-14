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
        Schema::create("suppliers", function (Blueprint $table) {
            $table->id();
            $table->string("code", 20)->unique();
            $table->string("name", 100);
            $table->string("contact_person", 100)->nullable();
            $table->string("phone", 20)->nullable();
            $table->string("email", 100)->nullable();
            $table->text("address")->nullable();
            $table->string("city", 100)->nullable();
            $table->string("npwp", 30)->nullable();
            $table->integer("payment_terms")->default(30); // Jatuh tempo dalam hari
            $table->boolean("is_active")->default(true);
            $table->text("notes")->nullable();
            $table->timestamps();

            $table->index("name");
            $table->index("is_active");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("suppliers");
    }
};
