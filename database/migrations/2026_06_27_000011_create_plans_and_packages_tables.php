<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_packages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('name_ar');
            $table->unsignedInteger('credits')->nullable(); // null = unlimited (grants a tier)
            $table->unsignedInteger('price');               // EGP
            $table->string('grants_tier')->nullable();      // for "Pro" unlimited
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('name_ar');
            $table->string('tier'); // basic | gold | premium (merchant_profiles.subscription_tier)
            $table->unsignedInteger('price'); // EGP / month
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
        Schema::dropIfExists('credit_packages');
    }
};
