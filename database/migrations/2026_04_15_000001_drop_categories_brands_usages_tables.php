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

   

        // Drop pivot tables
        Schema::dropIfExists('category_archetype');
        Schema::dropIfExists('category_type');
        Schema::dropIfExists('type_usage');

        // Drop the main tables
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('usages');
        Schema::dropIfExists('types');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate brands table
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // Recreate categories table
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // Recreate usages table
        Schema::create('usages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // Recreate types table
        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // Add brand_id back to items
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_id')->nullable()->after('manufactured_at');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
        });

        // Recreate pivot tables
        Schema::create('category_type', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('type_id');
            $table->timestamps();
            
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('type_id')->references('id')->on('types')->onDelete('cascade');
        });

        Schema::create('type_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('type_id');
            $table->unsignedBigInteger('usage_id');
            $table->timestamps();
            
            $table->foreign('type_id')->references('id')->on('types')->onDelete('cascade');
            $table->foreign('usage_id')->references('id')->on('usages')->onDelete('cascade');
        });

        Schema::create('category_archetype', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('archetype_id');
            $table->timestamps();
            
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('archetype_id')->references('id')->on('archetypes')->onDelete('cascade');
        });
    }
};

