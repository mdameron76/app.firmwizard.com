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
        Schema::create('gbp_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->onDelete('cascade');
            $table->string('gbp_location_id')->index();
            $table->string('place_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->text('formatted_address')->nullable();
            $table->string('formatted_phone_number')->nullable();
            $table->string('website')->nullable();
            $table->json('opening_hours')->nullable();
            $table->json('types')->nullable(); // Categories
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('user_ratings_total')->nullable();
            $table->json('reviews')->nullable();
            $table->json('photos')->nullable(); // Array of photo_reference strings
            $table->json('raw_data')->nullable(); // Full Google Places API response
            $table->timestamp('last_synced_at')->nullable();
            $table->string('status')->default('connected'); // connected, disconnected, auth_error
            $table->timestamps();

            // Unique constraint on firm_id + gbp_location_id
            $table->unique(['firm_id', 'gbp_location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gbp_locations');
    }
};
