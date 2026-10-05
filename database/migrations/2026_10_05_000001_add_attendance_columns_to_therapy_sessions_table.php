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
        Schema::table('therapy_sessions', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('notes'); // waktu scan/checkin aktual
            $table->string('attendance_token', 64)->nullable()->unique()->after('checked_in_at'); // signed token untuk QR
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('therapy_sessions', function (Blueprint $table) {
            $table->dropColumn(['checked_in_at', 'attendance_token']);
        });
    }
};
