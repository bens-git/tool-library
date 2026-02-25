<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: Thumbnail functionality has been removed.
     * Images are compressed on store instead using the ItemController::storeImage method.
     */
    public function up(): void
    {
        // Thumbnail columns are no longer needed
        // Images are compressed to max 1200px on upload
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed - thumbnails were never added
    }
};
