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
        Schema::table('items', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign('items_brand_id_foreign');

            $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Drop the modified foreign key
            $table->dropForeign(['brand_id']);

            // Restore the original foreign key (assuming it was not nullable)
            $table->foreign('brand_id')->references('id')->on('brands');
        });
    }
};
