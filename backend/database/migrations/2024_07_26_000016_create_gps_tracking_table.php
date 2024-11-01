<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGpsTrackingTable extends Migration
{
    public function up()
    {
        Schema::create('gps_tracking', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tracked_at')->nullable();
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('gps_tracker_id')->constrained('gps_trackers');
            $table->timestamps();
            $table->unique('id');
            $table->index('item_id');
            $table->index('gps_tracker_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('gps_tracking');
    }
}