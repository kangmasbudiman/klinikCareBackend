<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("medical_records", function (Blueprint $table) {
            $table->text("soap_subjective")->nullable()->after("follow_up_date");
            $table->text("soap_objective")->nullable()->after("soap_subjective");
            $table->text("soap_assessment")->nullable()->after("soap_objective");
            $table->text("soap_plan")->nullable()->after("soap_assessment");
        });
    }

    public function down(): void
    {
        Schema::table("medical_records", function (Blueprint $table) {
            $table->dropColumn([
                "soap_subjective",
                "soap_objective",
                "soap_assessment",
                "soap_plan",
            ]);
        });
    }
};
