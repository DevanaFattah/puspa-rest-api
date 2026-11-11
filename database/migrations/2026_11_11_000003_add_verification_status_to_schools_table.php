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
        Schema::table('schools', function (Blueprint $table) {
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])
                ->default('verified')
                ->after('pic_name');
            $table->dateTime('verified_at')->nullable()->after('verification_status');

            $table->index('verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropIndex(['verification_status']);
            $table->dropColumn(['verification_status', 'verified_at']);
        });
    }
};

