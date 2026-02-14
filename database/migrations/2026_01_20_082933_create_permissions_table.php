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
        Schema::create("permissions", function (Blueprint $table) {
            $table->id();
            $table->string("name", 100)->unique(); // e.g., 'users.view', 'users.create'
            $table->string("display_name", 150);
            $table->text("description")->nullable();
            $table->string("module", 50); // e.g., 'users', 'patients', 'services'
            $table->string("action", 50); // e.g., 'view', 'create', 'edit', 'delete'
            $table->integer("sort_order")->default(0);
            $table->timestamps();

            $table->index("module");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("permissions");
    }
};
