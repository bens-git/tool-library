<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('archetype_access_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archetype_id')->constrained()->onDelete('cascade');
            $table->decimal('base_credit_value', 8, 2)->nullable();
            $table->decimal('current_daily_rate', 8, 2)->default(1.00);
            $table->decimal('current_weekly_rate', 8, 2)->default(5.00);
            $table->integer('vote_count')->default(0);
            $table->decimal('vote_total', 10, 2)->default(0);
            $table->decimal('average_vote', 8, 2)->default(0);
            $table->decimal('decay_rate', 8, 5)->default(0.01);
            $table->timestamp('last_rate_change')->nullable();
            $table->timestamps();
            
            $table->unique('archetype_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archetype_access_values');
    }
};
