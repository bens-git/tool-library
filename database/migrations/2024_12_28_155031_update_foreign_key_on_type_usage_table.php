<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateForeignKeyOnTypeUsageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('type_usage', function (Blueprint $table) {
            // Drop the existing foreign key
            $table->dropForeign(['type_id']);
            $table->dropForeign(['usage_id']);

            // Add the new foreign key with ON DELETE CASCADE
            $table->foreign('type_id')
                ->references('id')
                ->on('types')
                ->onDelete('cascade');



            // Add the new foreign key with ON DELETE CASCADE
            $table->foreign('usage_id')
                ->references('id')
                ->on('usages')
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
        Schema::table('type_usage', function (Blueprint $table) {
            // Drop the new foreign key
            $table->dropForeign(['type_id']);
            $table->dropForeign(['usage_id']);

            // Add the original foreign key with ON DELETE RESTRICT
            $table->foreign('type_id')
                ->references('id')
                ->on('types')
                ->onDelete('restrict');

            // Add the original foreign key with ON DELETE RESTRICT
            $table->foreign('usage_id')
                ->references('id')
                ->on('usages')
                ->onDelete('restrict');
        });
    }
}
