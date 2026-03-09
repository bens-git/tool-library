<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArchetypesTable extends Migration
{
    public function up()
    {
        Schema::create('archetypes', function (Blueprint $table) {
            $table->id()->unsigned();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('notes')->nullable();
            $table->string('code')->nullable();
            $table->boolean('modular')->default(true);
            $table->enum('resource', [
                'TOOL',
                'MATERIAL',
                'LABOR',
                'RIDESHARE',
                'FURNITURE',
                'KITCHEN',
                'ELECTRONICS',
                'SPORTS',
                'OUTDOOR',
                'PARTY',
                'BOOKS',
                'OTHER'
            ])->default('TOOL');
            $table->timestamps();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->unique('id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('archetypes');
    }
}
