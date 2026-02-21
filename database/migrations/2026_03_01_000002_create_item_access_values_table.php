<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Item Access Values - credit cost for each item based on purchase price
     */
    public function up(): void
    {
        Schema::create('item_access_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            
            // Base value calculated from purchase price
            $table->decimal('purchase_value', 12, 2)->nullable();
            $table->decimal('base_credit_value', 12, 2)->nullable();
            
            // Current credit rate (can be modified by voting)
            $table->decimal('current_daily_rate', 8, 2)->default(1);
            $table->decimal('current_weekly_rate', 8, 2)->default(5);
            
            // Voting data
            $table->integer('vote_count')->default(0);
            $table->decimal('vote_total', 12, 2)->default(0);
            $table->decimal('average_vote', 8, 2)->default(0);
            
            // Decay settings
            $table->decimal('decay_rate', 5, 4)->default(0.0001); // Daily decay rate
            $table->timestamp('last_rate_change')->nullable();
            
            $table->timestamps();
            
            $table->unique('item_id');
            $table->index('current_daily_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_access_values');
    }
};

