<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaintenanceSchedulesTable extends Migration
{
    public function up()
    {
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('frequency')->nullable();
            $table->foreignId('maintenance_task_id')->constrained('maintenance_tasks');
            $table->dateTime('scheduled_for')->nullable();
            $table->foreignId('item_id')->constrained('items');
            $table->timestamps();
            $table->unique('id');
            $table->index('maintenance_task_id');
            $table->index('item_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('maintenance_schedules');
    }
}