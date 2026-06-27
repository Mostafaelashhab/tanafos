<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('merchant_profile_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('rating');          // overall 1–5
            $table->unsignedTinyInteger('quality_score')->nullable();   // 1–5
            $table->unsignedTinyInteger('delivery_score')->nullable();  // 1–5
            $table->unsignedTinyInteger('response_score')->nullable();  // 1–5
            $table->text('comment')->nullable();
            $table->timestamps();

            // One review per completed request (the buyer reviews the winning merchant).
            $table->unique('request_id');
            $table->index('merchant_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
