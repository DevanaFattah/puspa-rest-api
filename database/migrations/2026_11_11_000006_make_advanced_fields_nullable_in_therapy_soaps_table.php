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
        Schema::table('therapy_soaps', function (Blueprint $table) {
            $table->text('implementation')->nullable()->change();
            $table->text('advanced_assessment')->nullable()->change();
            $table->text('advanced_plan')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('therapy_soaps', function (Blueprint $table) {
            $table->text('implementation')->nullable(false)->change();
            $table->text('advanced_assessment')->nullable(false)->change();
            $table->text('advanced_plan')->nullable(false)->change();
        });
    }
};

