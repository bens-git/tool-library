<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RenameTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('types', 'archetypes');
        Schema::rename('type_images', 'archetype_images');

        DB::statement('ALTER TABLE archetype_images CHANGE type_id archetype_id BIGINT UNSIGNED');

        Schema::rename('type_usage', 'archetype_usage');

        DB::statement('ALTER TABLE archetype_usage CHANGE type_id archetype_id BIGINT UNSIGNED');

        Schema::rename('category_type', 'category_archetype');

        DB::statement('ALTER TABLE category_archetype CHANGE type_id archetype_id BIGINT UNSIGNED');
       
        DB::statement('ALTER TABLE items CHANGE type_id archetype_id BIGINT UNSIGNED');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('archetypes', 'types');

        DB::statement('ALTER TABLE archetype_images CHANGE archetype_id type_id BIGINT UNSIGNED');

        Schema::rename('archetype_images', 'type_images');

        DB::statement('ALTER TABLE archetype_usage CHANGE archetype_id type_id BIGINT UNSIGNED');

        Schema::rename('archetype_usage', 'type_usage');

        DB::statement('ALTER TABLE category_archetype CHANGE archetype_id type_id BIGINT UNSIGNED');

        Schema::rename('category_archetype', 'category_type');

        DB::statement('ALTER TABLE items CHANGE archetype_id type_id BIGINT UNSIGNED');
    }
}
