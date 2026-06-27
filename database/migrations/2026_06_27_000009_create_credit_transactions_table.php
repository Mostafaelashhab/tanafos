<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_profile_id')->constrained()->cascadeOnDelete();

            // purchase / consume / refund / bonus / subscription
            $table->string('type');
            $table->integer('amount');              // signed: + credits added, − consumed
            $table->integer('balance_after');
            $table->unsignedInteger('price')->nullable(); // EGP paid, for purchases
            $table->string('description')->nullable();
            $table->nullableMorphs('reference');    // e.g. the Offer that consumed a credit
            $table->timestamps();

            $table->index(['merchant_profile_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
