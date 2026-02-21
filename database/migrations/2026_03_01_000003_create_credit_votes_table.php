<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Credit Votes - community voting on item credit rates
     */
    public function up(): void
    {
        Schema::create('credit_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            
            // The vote value (e.g., 1.5 credits/day)
            $table->decimal('vote_value', 8, 2);
            
            // Optional reason for the vote
            $table->text('reason')->nullable();
            
            // User's current balance (for weighted voting)
            $table->decimal('user_balance_at_vote', 15, 2)->nullable();
            
            $table->timestamps();
            
            $table->unique(['user_id', 'item_id']);
            $table->index(['item_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_votes');
    }
};

