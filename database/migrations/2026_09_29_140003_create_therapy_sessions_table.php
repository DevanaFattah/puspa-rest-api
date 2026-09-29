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
        Schema::create('therapy_sessions', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('schedule_id', 26);
            $table->unsignedTinyInteger('session_number');
            $table->date('session_date');
            $table->char('therapist_id', 26)->nullable();
            $table->enum('status', [
                'pending',
                'present',
                'absent',
                'excused',
                'sick',
                'therapist_absent',
                'therapist_excused',
                'cancelled'
            ])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('schedule_id')->references('id')->on('schedules')->onDelete('cascade');
            $table->foreign('therapist_id')->references('id')->on('therapists')->onDelete('set null');
            $table->index('schedule_id');
            $table->index('session_date');
            $table->index('status');
            $table->index(['schedule_id', 'session_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('therapy_sessions');
    }
};
