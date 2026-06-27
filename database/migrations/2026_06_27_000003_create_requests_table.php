<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // Budget range (EGP by default)
            $table->unsignedInteger('budget_min')->nullable();
            $table->unsignedInteger('budget_max')->nullable();
            $table->string('currency', 3)->default('EGP');

            // Location for geo matching
            $table->string('city')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            $table->enum('condition', ['new', 'used', 'any'])->default('any');
            $table->enum('urgency', ['low', 'normal', 'high'])->default('normal');
            $table->enum('payment_method', ['cash', 'card', 'installment', 'any'])->default('any');
            $table->boolean('warranty_required')->default(false);
            $table->string('preferred_delivery')->nullable();

            // AI-enriched structured specs (Phase: AI)
            $table->json('specifications')->nullable();

            $table->enum('status', ['draft', 'open', 'matched', 'closed', 'expired', 'completed'])
                ->default('draft')->index();

            // Winning offer (FK added with the offers table in Phase 3)
            $table->unsignedBigInteger('selected_offer_id')->nullable()->index();

            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category_id']);
            $table->index(['lat', 'lng']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
