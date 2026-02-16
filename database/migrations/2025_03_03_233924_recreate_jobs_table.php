<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            Schema::dropIfExists('jobs');
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            $table->foreignId('base_id')->constrained('archetypes')->onDelete('cascade');
            $table->foreignId('component_id')->nullable()->constrained('archetypes')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('archetypes')->onDelete('cascade');
            $table->foreignId('tool_id')->nullable()->constrained('archetypes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            Schema::dropIfExists('jobs');
        });

        Schema::table('jobs', function (Blueprint $table) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();

                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

                $table->foreignId('material_id')->constrained('types')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('types')->onDelete('cascade');
                $table->foreignId('tool_id')->constrained('types')->onDelete('cascade');
            });
        });
    }
}
