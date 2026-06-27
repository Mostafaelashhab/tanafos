<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('condition', ['new', 'used', 'any'])->default('used');
            $table->string('city')->nullable();
            $table->string('currency', 3)->default('EGP');

            $table->unsignedInteger('starting_price');
            $table->unsignedInteger('bid_increment')->default(50);
            $table->unsignedInteger('reserve_price')->nullable();
            $table->unsignedInteger('current_price');   // highest bid, or starting price
            $table->unsignedInteger('bids_count')->default(0);
            $table->unsignedBigInteger('highest_bid_id')->nullable()->index();

            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['live', 'ended', 'cancelled'])->default('live')->index();
            $table->timestamp('ends_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
