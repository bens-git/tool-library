<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTypesTable extends Migration
{
    public function up()
    {
        Schema::create('types', function (Blueprint $table) {
            $table->id()->unsigned();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('notes')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->unique('id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('types');
    }
}
