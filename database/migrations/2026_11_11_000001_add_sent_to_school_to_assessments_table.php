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
        Schema::table('assessments', function (Blueprint $table) {
            $table->dateTime('sent_to_school_at')->nullable()->after('report_uploaded_at');
            $table->index('sent_to_school_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex(['sent_to_school_at']);
            $table->dropColumn('sent_to_school_at');
        });
    }
};

