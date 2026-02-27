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
        Schema::table('credit_votes', function (Blueprint $table) {
            $table->foreignId('archetype_id')->nullable()->constrained()->onDelete('set null');
            $table->index('archetype_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_votes', function (Blueprint $table) {
            $table->dropForeign(['archetype_id']);
            $table->dropIndex(['archetype_id']);
            $table->dropColumn('archetype_id');
        });
    }
};
