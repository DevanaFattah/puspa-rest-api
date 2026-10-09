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
        Schema::create('therapy_soaps', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('therapy_session_id', 26)->unique();

            $table->dateTime('performed_at');
            $table->text('therapy_diagnosis');
            $table->text('interventions');

            // S.O.A.P
            $table->text('subjective');
            $table->text('objective');
            $table->text('assessment');
            $table->text('plan');

            // Implementation & Advanced fields
            $table->text('implementation');
            $table->text('advanced_assessment');
            $table->text('advanced_plan');

            $table->timestamps();

            // Foreign key & Index
            $table->foreign('therapy_session_id')
                ->references('id')
                ->on('therapy_sessions')
                ->onDelete('cascade');

            $table->index('performed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('therapy_soaps');
    }
};
