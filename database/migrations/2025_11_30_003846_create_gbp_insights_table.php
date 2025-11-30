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
        Schema::create('gbp_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained('firms')->onDelete('cascade');
            $table->string('location_id')->nullable()->comment('GBP Location ID');
            $table->string('location_name')->nullable();
            $table->date('date');

            // Metrics
            $table->integer('views_search')->default(0)->comment('Views from search');
            $table->integer('views_maps')->default(0)->comment('Views from maps');
            $table->integer('actions_website')->default(0)->comment('Website clicks');
            $table->integer('actions_phone')->default(0)->comment('Phone calls');
            $table->integer('actions_driving_directions')->default(0)->comment('Direction requests');
            $table->integer('photos_count_merchant')->default(0)->comment('Business photos');
            $table->integer('photos_count_customers')->default(0)->comment('Customer photos');
            $table->integer('photos_views_merchant')->default(0);
            $table->integer('photos_views_customers')->default(0);

            // Search queries (top keywords that led to profile)
            $table->json('search_queries')->nullable();

            // Raw API response for debugging
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->index(['firm_id', 'date']);
            $table->index('location_id');
            $table->unique(['firm_id', 'location_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gbp_insights');
    }
};
