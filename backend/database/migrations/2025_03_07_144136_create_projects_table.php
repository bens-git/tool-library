<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
        });

        Schema::create('project_job', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');

            $table->unsignedBigInteger('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Disable foreign key constraints

        Schema::dropIfExists('projects');
        Schema::dropIfExists('project_job');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // Re-enable foreign key constraints
    }
};
