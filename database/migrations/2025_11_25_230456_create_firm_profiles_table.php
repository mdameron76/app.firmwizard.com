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
        Schema::create('firm_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->onDelete('cascade');
            $table->string('main_office_city')->nullable();
            $table->string('main_office_state')->nullable();
            $table->string('main_office_zip')->nullable();
            $table->json('practice_areas_json')->nullable();
            $table->json('brand_voice_json')->nullable();
            $table->json('target_demographics_json')->nullable();
            $table->json('service_areas_json')->nullable();
            $table->json('marketing_goals_json')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
            
            $table->index('firm_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firm_profiles');
    }
};
