<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaintenanceLogsTable extends Migration
{
    public function up()
    {
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('performed_at')->nullable();
            $table->foreignId('maintenance_task_id')->constrained('maintenance_tasks');
            $table->foreignId('performed_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->unique('id');
            $table->index('maintenance_task_id');
            $table->index('performed_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('maintenance_logs');
    }
}
