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
        Schema::table('children', function (Blueprint $table) {
            $table->char('school_id', 26)->nullable()->after('family_id');

            $table->foreign('school_id')
                ->references('id')
                ->on('schools')
                ->nullOnDelete();

            $table->index('school_id', 'children_school_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropIndex('children_school_id_idx');
            $table->dropColumn('school_id');
        });
    }
};

