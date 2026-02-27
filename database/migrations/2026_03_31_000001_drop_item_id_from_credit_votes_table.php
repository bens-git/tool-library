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
        // First, find and drop all foreign key constraints that reference item_id
        // (MySQL requires dropping FK before dropping the unique index it references)
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'credit_votes' 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        
        foreach ($foreignKeys as $fk) {
            try {
                DB::statement("ALTER TABLE credit_votes DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
            } catch (\Exception $e) {
                // Foreign key may already be dropped or have issues
            }
        }
        
        // Drop the unique constraint first (before dropping the column it references)
        // Note: The unique constraint includes item_id, so we must drop it first
        $constraints = DB::select("SHOW INDEX FROM credit_votes WHERE Key_name = 'credit_votes_user_id_item_id_unique'");
        
        if (count($constraints) > 0) {
            DB::statement('ALTER TABLE credit_votes DROP INDEX credit_votes_user_id_item_id_unique');
        }
        
        // Drop the index on item_id and created_at if it exists
        $indexes = DB::select("SHOW INDEX FROM credit_votes WHERE Key_name = 'credit_votes_item_id_created_at_index'");
        
        if (count($indexes) > 0) {
            DB::statement('ALTER TABLE credit_votes DROP INDEX credit_votes_item_id_created_at_index');
        }
        
        // Drop the item_id column if it exists
        $columns = DB::select("SHOW COLUMNS FROM credit_votes WHERE Field = 'item_id'");
        
        if (count($columns) > 0) {
            DB::statement('ALTER TABLE credit_votes DROP COLUMN item_id');
        }
        
        // Add a new unique constraint for archetype voting if it doesn't exist
        // Each user can only vote once per archetype
        $uniqueExists = DB::select("SHOW INDEX FROM credit_votes WHERE Key_name = 'credit_votes_user_archetype_unique'");
        
        if (count($uniqueExists) === 0) {
            DB::statement('ALTER TABLE credit_votes ADD UNIQUE INDEX credit_votes_user_archetype_unique (user_id, archetype_id)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new unique constraint
        try {
            DB::statement('ALTER TABLE credit_votes DROP INDEX credit_votes_user_archetype_unique');
        } catch (\Exception $e) {
            // Index may not exist
        }
        
        // Re-add the item_id column
        $columns = DB::select("SHOW COLUMNS FROM credit_votes WHERE Field = 'item_id'");
        
        if (count($columns) === 0) {
            DB::statement('ALTER TABLE credit_votes ADD COLUMN item_id BIGINT UNSIGNED NULL AFTER user_id');
            
            // Re-add the unique constraint (we need to handle NULL values differently)
            // This is complex in MySQL, so we'll skip the unique constraint in down()
            // In production, you'd need to handle this more carefully
        }
    }
};

