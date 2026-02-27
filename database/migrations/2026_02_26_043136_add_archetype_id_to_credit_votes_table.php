<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add archetype_id to credit_votes table - this also creates the table if it doesn't exist
     * (needed because this migration runs before 2026_03_01_000003_create_credit_votes_table)
     */
    public function up(): void
    {
        // Check if the table already exists (it might have been created by a previous run attempt)
        if (!Schema::hasTable('credit_votes')) {
            Schema::create('credit_votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('item_id')->nullable()->constrained()->onDelete('cascade');
                
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
        
        // Now add the archetype_id column if it doesn't exist
        if (Schema::hasTable('credit_votes') && !Schema::hasColumn('credit_votes', 'archetype_id')) {
            Schema::table('credit_votes', function (Blueprint $table) {
                $table->foreignId('archetype_id')->nullable()->constrained()->onDelete('set null');
                $table->index('archetype_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('credit_votes') && Schema::hasColumn('credit_votes', 'archetype_id')) {
            Schema::table('credit_votes', function (Blueprint $table) {
                $table->dropForeign(['archetype_id']);
                $table->dropIndex(['archetype_id']);
                $table->dropColumn('archetype_id');
            });
        }
    }
};

