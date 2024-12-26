<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('serial')->nullable();
            $table->text('description')->nullable();
            $table->decimal('purchase_value', 10, 2)->nullable();
            $table->dateTime('purchased_at')->nullable();
            $table->dateTime('manufactured_at')->nullable();
            $table->foreignId('owned_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('type_id')->constrained('types');
            $table->foreignId('gps_tracker_id')->nullable()->constrained('gps_trackers');
            $table->foreignId('brand_id')->nullable()->constrained('brands');
            $table->timestamps();
            $table->unique('id');
            $table->index('owned_by');
            $table->index('type_id');
            $table->index('gps_tracker_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('items');
    }
}
