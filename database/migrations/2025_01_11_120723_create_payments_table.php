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
            $table->uuid('id')->primary();
            $table->string('bank_payment_id')->unique()->nullable();
            $table->foreignUuid('order_id')->references('id')->on('orders')->cascadeOnDelete(); // Link to Order
            $table->string('tran_id')->unique();
            $table->string('bank_tran_id')->unique()->nullable();
            $table->string('refund_ref_id')->nullable();
            $table->string('status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default(config('payment.default_currency'));

            $table->timestamps(); // Add timestamps if needed
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
