<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            $table->foreignId('material_id')->constrained('types')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('types')->onDelete('cascade');
            $table->foreignId('tool_id')->constrained('types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs');
    }
}
