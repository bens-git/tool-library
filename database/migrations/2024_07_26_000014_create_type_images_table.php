<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTypeImagesTable extends Migration
{
    public function up()
    {
        Schema::create('type_images', function (Blueprint $table) {
            $table->id();
            $table->string('path')->nullable();
            $table->foreignId('type_id')->constrained('types');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->index('type_id');
            $table->index('created_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('type_images');
    }
}
