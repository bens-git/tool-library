<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaintenanceTasksTable extends Migration
{
    public function up()
    {
        Schema::create('maintenance_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('frequency')->nullable();
            $table->foreignId('type_id')->constrained('types');
            $table->timestamps();
            $table->unique('id');
            $table->index('type_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('maintenance_tasks');
    }
}