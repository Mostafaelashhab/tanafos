<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_profile_id')->constrained()->cascadeOnDelete();

            // Match quality 0–100, plus the distance used (km) for transparency.
            $table->unsignedTinyInteger('quality_score')->default(0);
            $table->decimal('distance_km', 8, 2)->nullable();

            $table->enum('status', ['notified', 'viewed', 'offered', 'ignored', 'expired'])
                ->default('notified')->index();

            // Set when a credit is consumed (on offer submission — Phase 3).
            $table->timestamp('charged_at')->nullable();
            $table->timestamp('viewed_at')->nullable();

            $table->timestamps();

            // One lead per (request, merchant).
            $table->unique(['request_id', 'merchant_profile_id']);
            $table->index(['merchant_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
