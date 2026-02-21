<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add credit-related fields to rentals table
     */
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->decimal('credits_charged', 10, 2)->nullable()->after('status');
            $table->decimal('credits_paid', 10, 2)->nullable()->after('credits_charged');
            $table->timestamp('credits_paid_at')->nullable()->after('credits_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn(['credits_charged', 'credits_paid', 'credits_paid_at']);
        });
    }
};

