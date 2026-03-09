<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsagesTable extends Migration
{
    public function up()
    {
        Schema::create('usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('used_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items');
            $table->dateTime('used_at')->nullable();
            $table->enum('status', ['active', 'booked', 'completed', 'overdue', 'holding'])->nullable();
            $table->decimal('credits_charged', 10, 2)->nullable();
            $table->decimal('credits_paid', 10, 2)->nullable();
            $table->timestamp('credits_paid_at')->nullable();
            $table->timestamps();
            $table->unique('id');
            $table->index('used_by');
            $table->index('item_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('usages');
    }
}
