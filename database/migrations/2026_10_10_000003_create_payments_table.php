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
        Schema::create('payments', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('invoice_id', 26);
            $table->char('child_id', 26);
            $table->char('guardian_id', 26);
            $table->enum('transaction_type', ['Pemasukan', 'Ke periode berikutnya', 'Pengembalian dana'])->default('Pemasukan');
            $table->string('payment_method')->default('-'); // Transfer bank, Tunai, QRIS, -
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('receipt_number')->nullable();
            $table->string('proof_file_path')->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
            $table->foreign('guardian_id')->references('id')->on('guardians')->onDelete('cascade');

            $table->index('invoice_id');
            $table->index('child_id');
            $table->index('guardian_id');
            $table->index('transaction_type');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
