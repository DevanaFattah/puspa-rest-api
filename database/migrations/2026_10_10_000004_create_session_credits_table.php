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
        Schema::create('session_credits', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('child_id', 26);
            $table->char('guardian_id', 26);
            $table->char('source_invoice_id', 26);
            $table->unsignedInteger('unused_session_count')->default(0);
            $table->decimal('credit_amount', 12, 2)->default(0);
            $table->boolean('is_used')->default(false);
            $table->char('used_in_invoice_id', 26)->nullable();
            $table->timestamps();

            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
            $table->foreign('guardian_id')->references('id')->on('guardians')->onDelete('cascade');
            $table->foreign('source_invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('used_in_invoice_id')->references('id')->on('invoices')->onDelete('set null');

            $table->index('child_id');
            $table->index('guardian_id');
            $table->index('is_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_credits');
    }
};
