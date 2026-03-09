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
        Schema::create('message_poll_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('poll_id');
            $table->string('option_text');
            $table->integer('vote_count')->default(0);
            $table->timestamps();

            $table->foreign('poll_id')
                ->references('id')
                ->on('message_polls')
                ->onDelete('cascade');

            $table->index('poll_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_poll_options');
    }
};

