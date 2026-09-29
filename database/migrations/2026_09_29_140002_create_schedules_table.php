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
        Schema::create('schedules', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('child_id', 26);
            $table->char('therapist_id', 26);
            $table->enum('therapy_type', ['okupasi', 'fisio', 'wicara', 'paedagog']);
            $table->unsignedTinyInteger('day_of_week'); // 1 = Senin, ..., 7 = Minggu
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('total_meetings');
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['aktif', 'non_aktif', 'selesai'])->default('aktif');
            $table->timestamps();

            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
            $table->foreign('therapist_id')->references('id')->on('therapists')->onDelete('restrict');
            $table->index('child_id');
            $table->index('therapist_id');
            $table->index('status');
            $table->index(['therapy_type', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
