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
        Schema::create('gbp_locations_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gbp_location_id')->constrained('gbp_locations')->onDelete('cascade');
            $table->foreignId('firm_id')->constrained()->onDelete('cascade');
            $table->string('gbp_location_string_id')->index(); // The GBP numeric ID
            $table->string('place_id')->index();

            // Snapshot of location data at this point in time
            $table->string('name')->nullable();
            $table->text('formatted_address')->nullable();
            $table->string('formatted_phone_number')->nullable();
            $table->string('website')->nullable();
            $table->json('opening_hours')->nullable();
            $table->json('types')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('user_ratings_total')->nullable();
            $table->json('reviews')->nullable(); // Top 5 most recent reviews
            $table->json('photos')->nullable(); // Photo references
            $table->json('raw_data')->nullable(); // Full API response

            $table->timestamp('synced_at')->index(); // When this snapshot was taken
            $table->timestamps();

            // Index for querying history by firm and date
            $table->index(['firm_id', 'synced_at']);
            $table->index(['gbp_location_id', 'synced_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gbp_locations_history');
    }
};
