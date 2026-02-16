<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTypeUsageTable extends Migration
{
    public function up()
    {
        Schema::create('type_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->constrained('types');
            $table->foreignId('usage_id')->constrained('usages');
            $table->timestamps();
            $table->unique('id');
            $table->index('type_id');
            $table->index('usage_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('type_usage');
    }
}