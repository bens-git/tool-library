
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoryTypeTable extends Migration
{
    public function up()
    {
        Schema::create('category_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('type_id')->constrained('types');
            $table->timestamps();
            $table->unique('id');
            $table->index('category_id');
            $table->index('type_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('category_type');
    }
}