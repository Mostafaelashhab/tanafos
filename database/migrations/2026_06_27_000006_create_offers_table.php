<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedInteger('price');
            $table->string('currency', 3)->default('EGP');
            $table->string('warranty')->nullable();
            $table->unsignedSmallInteger('delivery_days')->nullable();
            $table->text('description')->nullable();
            $table->boolean('negotiation_enabled')->default(true);

            $table->enum('status', ['submitted', 'shortlisted', 'accepted', 'rejected', 'withdrawn'])
                ->default('submitted')->index();

            $table->timestamps();

            // One live offer per merchant per request.
            $table->unique(['request_id', 'merchant_profile_id']);
            $table->index(['request_id', 'status']);
        });

        // Winning offer FK on requests (column created nullable in Phase 1).
        Schema::table('requests', function (Blueprint $table) {
            $table->foreign('selected_offer_id')->references('id')->on('offers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['selected_offer_id']);
        });

        Schema::dropIfExists('offers');
    }
};
