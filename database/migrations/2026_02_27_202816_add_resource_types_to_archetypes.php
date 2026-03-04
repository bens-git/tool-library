<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Comprehensive list of resource types for community sharing:
     * - TOOL: Power tools, hand tools, garden equipment
     * - MATERIAL: Building materials, craft supplies, raw materials
     * - LABOR: Manual labor, help with tasks, skilled work
     * - RIDESHARE: Transportation, carpooling, delivery
     * - FURNITURE: Tables, chairs, desks, event furniture
     * - KITCHEN: Kitchen equipment, appliances, cookware
     * - ELECTRONICS: Gadgets, devices, AV equipment
     * - SPORTS: Sports equipment, fitness gear
     * - OUTDOOR: Camping gear, outdoor equipment
     * - PARTY: Party supplies, decorations, event equipment
     * - BOOKS: Educational materials, textbooks, manuals
     * - OTHER: Miscellaneous items that don't fit other categories
     */
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = DB::connection()->getDriverName();
        $resourceTypes = [
            'TOOL', 
            'MATERIAL', 
            'LABOR', 
            'RIDESHARE', 
            'FURNITURE', 
            'KITCHEN',
            'ELECTRONICS',
            'SPORTS',
            'OUTDOOR',
            'PARTY',
            'BOOKS',
            'OTHER'
        ];
        
        if (!Schema::hasColumn('archetypes', 'resource')) {
            // Column doesn't exist - add it with the comprehensive enum
            if ($connection === 'mysql') {
                Schema::table('archetypes', function (Blueprint $table) use ($resourceTypes) {
                    $enumValues = implode("', '", $resourceTypes);
                    DB::statement("ALTER TABLE archetypes ADD COLUMN resource ENUM('{$enumValues}') DEFAULT 'TOOL'");
                });
            } elseif ($connection === 'pgsql') {
                Schema::table('archetypes', function (Blueprint $table) use ($resourceTypes) {
                    $table->enum('resource', $resourceTypes)->default('TOOL')->nullable(false);
                });
            }
        } else {
            // Column exists - modify it to include the comprehensive list
            if ($connection === 'mysql') {
                DB::statement("ALTER TABLE archetypes MODIFY COLUMN resource ENUM(
                    'TOOL', 
                    'MATERIAL', 
                    'LABOR', 
                    'RIDESHARE', 
                    'FURNITURE', 
                    'KITCHEN',
                    'ELECTRONICS',
                    'SPORTS',
                    'OUTDOOR',
                    'PARTY',
                    'BOOKS',
                    'OTHER'
                ) DEFAULT 'TOOL'");
            } elseif ($connection === 'pgsql') {
                DB::statement("ALTER TABLE archetypes ALTER COLUMN resource TYPE VARCHAR(50)");
                DB::statement("ALTER TABLE archetypes ALTER COLUMN resource SET DEFAULT 'TOOL'");
                DB::statement("ALTER TABLE archetypes ADD CONSTRAINT resource_check CHECK (
                    resource IN (
                        'TOOL', 
                        'MATERIAL', 
                        'LABOR', 
                        'RIDESHARE', 
                        'FURNITURE', 
                        'KITCHEN',
                        'ELECTRONICS',
                        'SPORTS',
                        'OUTDOOR',
                        'PARTY',
                        'BOOKS',
                        'OTHER'
                    )
                )");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = DB::connection()->getDriverName();
        
        if (Schema::hasColumn('archetypes', 'resource')) {
            if ($connection === 'mysql') {
                DB::statement("ALTER TABLE archetypes MODIFY COLUMN resource ENUM('TOOL', 'MATERIAL') DEFAULT 'TOOL'");
            } elseif ($connection === 'pgsql') {
                DB::statement("ALTER TABLE archetypes DROP CONSTRAINT IF EXISTS resource_check");
                DB::statement("ALTER TABLE archetypes ALTER COLUMN resource TYPE VARCHAR(50)");
                DB::statement("ALTER TABLE archetypes ALTER COLUMN resource SET DEFAULT 'TOOL'");
            }
        }
    }
};
