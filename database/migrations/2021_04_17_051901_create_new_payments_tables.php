<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_gateways', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('customer_id');
            $table->string('gateway_name')->nullable();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('method_key')->unique();
            $table->boolean('default_card')->nullable()->default(false);
            $table->string('fingerprint')->unique();
            $table->string('owner_name')->nullable();
            $table->string('network')->nullable();
            $table->string('type')->nullable();
            $table->string('last4')->nullable();
            $table->string('expires')->nullable();
            $table->string('origin')->nullable();
            $table->string('verification_check')->nullable();
            $table->foreignUuid('payment_gateway_id')->nullable()->constrained()->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('payment_intents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tracking_number')->nullable();
            $table->string('payment_gateway')->nullable();
            $table->json('payment_intent_info')->nullable();
            $table->foreignUuid('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('payment_intents');
    }
};
