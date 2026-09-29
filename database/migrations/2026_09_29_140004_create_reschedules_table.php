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
        Schema::create('reschedules', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('original_session_id', 26);
            $table->char('new_session_id', 26);
            $table->enum('requested_by', ['children', 'therapist', 'guardian']);
            $table->timestamps();

            $table->foreign('original_session_id')->references('id')->on('therapy_sessions')->onDelete('cascade');
            $table->foreign('new_session_id')->references('id')->on('therapy_sessions')->onDelete('cascade');
            $table->unique('original_session_id');
            $table->index('new_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reschedules');
    }
};
