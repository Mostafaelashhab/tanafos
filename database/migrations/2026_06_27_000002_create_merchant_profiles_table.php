<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('business_name');
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();

            // Location (real lat/lng for Haversine matching)
            $table->string('city')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // Trust & monetization
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('credits_balance')->default(0);
            $table->enum('subscription_tier', ['none', 'basic', 'gold', 'premium'])->default('none');

            // Cached performance aggregates (recomputed from reviews/offers)
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('completed_deals')->default(0);
            $table->unsignedInteger('response_minutes_avg')->nullable();
            $table->decimal('win_rate', 5, 2)->default(0); // percentage

            $table->timestamps();

            $table->index(['lat', 'lng']);
            $table->index('verified_at');
        });

        // Categories a merchant serves (matching filter).
        // Name follows Laravel's belongsToMany convention: category_merchant_profile.
        Schema::create('category_merchant_profile', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_profile_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'merchant_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_merchant_profile');
        Schema::dropIfExists('merchant_profiles');
    }
};
