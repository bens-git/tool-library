<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemUnavailableDatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_unavailable_dates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id'); // Foreign key to the items table
            $table->date('unavailable_date'); // Date when the item is unavailable
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
      
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('item_unavailable_dates');
    }
}
