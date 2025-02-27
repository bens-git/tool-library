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
        Schema::rename('types', 'resource_archetypes');
        Schema::rename('type_images', 'resource_archetype_images');

        DB::statement('ALTER TABLE resource_archetype_images CHANGE type_id resource_archetype_id BIGINT UNSIGNED');

        Schema::rename('type_usage', 'resource_archetype_usage');

        DB::statement('ALTER TABLE resource_archetype_usage CHANGE type_id resource_archetype_id BIGINT UNSIGNED');

        Schema::rename('category_type', 'category_resource_archetype');

        DB::statement('ALTER TABLE category_resource_archetype CHANGE type_id resource_archetype_id BIGINT UNSIGNED');
       
        DB::statement('ALTER TABLE items CHANGE type_id resource_archetype_id BIGINT UNSIGNED');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('resource_archetypes', 'types');

        DB::statement('ALTER TABLE resource_archetype_images CHANGE resource_archetype_id type_id BIGINT UNSIGNED');

        Schema::rename('resource_archetype_images', 'type_images');

        DB::statement('ALTER TABLE resource_archetype_usage CHANGE resource_archetype_id type_id BIGINT UNSIGNED');

        Schema::rename('resource_archetype_usage', 'type_usage');

        DB::statement('ALTER TABLE category_resource_archetype CHANGE resource_archetype_id type_id BIGINT UNSIGNED');

        Schema::rename('category_resource_archetype', 'category_type');

        DB::statement('ALTER TABLE items CHANGE resource_archetype_id type_id BIGINT UNSIGNED');
    }
}
