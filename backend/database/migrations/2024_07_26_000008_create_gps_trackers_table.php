<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGpsTrackersTable extends Migration
{
    public function up()
    {
        Schema::create('gps_trackers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number', 45)->nullable();
            $table->dateTime('installed_at')->nullable();
            $table->timestamps();
            $table->unique('id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('gps_trackers');
    }
}