<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->foreignId('doctor_id')
                ->nullable()
                ->after('department_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['queue_date', 'doctor_id']);
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->dropIndex(['queue_date', 'doctor_id']);
            $table->dropColumn('doctor_id');
        });
    }
};
