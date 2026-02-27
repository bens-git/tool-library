<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove item_id column from credit_votes since voting is now for archetypes only
     */
    public function up(): void
    {
        // First, drop the foreign key constraint
        // The constraint name follows Laravel's convention: credit_votes_item_id_foreign
        DB::statement('ALTER TABLE credit_votes DROP FOREIGN KEY credit_votes_item_id_foreign');
        
        // Drop the unique constraint
        // Note: The actual constraint name may vary, let's check first
        $constraints = DB::select("SHOW INDEX FROM credit_votes WHERE Key_name = 'credit_votes_user_id_item_id_unique'");
        
        if (count($constraints) > 0) {
            DB::statement('ALTER TABLE credit_votes DROP INDEX credit_votes_user_id_item_id_unique');
        }
        
        // Drop the index on item_id and created_at
        $indexes = DB::select("SHOW INDEX FROM credit_votes WHERE Key_name = 'credit_votes_item_id_created_at_index'");
        
        if (count($indexes) > 0) {
            DB::statement('ALTER TABLE credit_votes DROP INDEX credit_votes_item_id_created_at_index');
        }
        
        // Now drop the column
        DB::statement('ALTER TABLE credit_votes DROP COLUMN item_id');
        
        // Add a new unique constraint for archetype voting
        // Each user can only vote once per archetype
        DB::statement('ALTER TABLE credit_votes ADD UNIQUE INDEX credit_votes_user_archetype_unique (user_id, archetype_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new unique constraint
        DB::statement('ALTER TABLE credit_votes DROP INDEX credit_votes_user_archetype_unique');
        
        // Re-add the item_id column
        DB::statement('ALTER TABLE credit_votes ADD COLUMN item_id BIGINT UNSIGNED NULL AFTER user_id');
        
        // Re-add the foreign key
        DB::statement('ALTER TABLE credit_votes ADD CONSTRAINT credit_votes_item_id_foreign FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE');
        
        // Re-add the unique constraint (we need to handle NULL values differently)
        // This is complex in MySQL, so we'll skip the unique constraint in down()
        // In production, you'd need to handle this more carefully
    }
};

