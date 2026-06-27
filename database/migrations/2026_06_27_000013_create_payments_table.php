<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_profile_id')->constrained()->cascadeOnDelete();
            $table->string('kind');             // package | plan
            $table->string('item_key');         // credit_packages.key / plans.key
            $table->string('method');           // instapay | vodafone_cash
            $table->unsignedInteger('amount');  // EGP
            $table->string('sender_number');    // who paid (the merchant's wallet/number)
            $table->string('reference')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
