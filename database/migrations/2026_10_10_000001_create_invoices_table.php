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
        Schema::create('invoices', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('no_invoice')->unique();
            $table->char('child_id', 26);
            $table->char('guardian_id', 26);
            $table->enum('type', ['Bulanan', 'Paket Durasi'])->default('Bulanan');
            $table->string('period_label');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('status', [
                'BELUM DIBAYAR',
                'LUNAS',
                'PERIODE BERIKUTNYA',
                'DIKEMBALIKAN',
                'BERJALAN'
            ])->default('BELUM DIBAYAR');
            $table->date('issued_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
            $table->foreign('guardian_id')->references('id')->on('guardians')->onDelete('cascade');

            $table->index('no_invoice');
            $table->index('child_id');
            $table->index('guardian_id');
            $table->index('status');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
