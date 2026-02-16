<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateForeignKeyOnCategoryTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('category_type', function (Blueprint $table) {
            // Drop the existing foreign key
            $table->dropForeign(['type_id']);
            $table->dropForeign(['category_id']);

            // Add the new foreign key with ON DELETE CASCADE
            $table->foreign('type_id')
                ->references('id')
                ->on('types')
                ->onDelete('cascade');



            // Add the new foreign key with ON DELETE CASCADE
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('category_type', function (Blueprint $table) {
            // Drop the new foreign key
            $table->dropForeign(['type_id']);
            $table->dropForeign(['category_id']);

            // Add the original foreign key with ON DELETE RESTRICT
            $table->foreign('type_id')
                ->references('id')
                ->on('types')
                ->onDelete('restrict');

            // Add the original foreign key with ON DELETE RESTRICT
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('restrict');
        });
    }
}
