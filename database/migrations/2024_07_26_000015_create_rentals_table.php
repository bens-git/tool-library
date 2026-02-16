<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalsTable extends Migration
{
    public function up()
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rented_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items');
            $table->dateTime('rented_at')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->enum('status', ['active', 'booked', 'completed', 'overdue', 'holding'])->nullable();
            $table->tinyInteger('renter_punctuality')->default(1);
            $table->tinyInteger('owner_punctuality')->default(1);
            $table->timestamps();
            $table->unique('id');
            $table->index('rented_by');
            $table->index('item_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rentals');
    }
}
