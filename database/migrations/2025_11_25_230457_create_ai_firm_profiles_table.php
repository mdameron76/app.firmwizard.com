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
        Schema::create('ai_firm_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->onDelete('cascade');
            $table->string('source'); // onboarding, collector, oz
            $table->longText('profile_json');
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            
            $table->index('firm_id');
            $table->index('source');
            $table->index(['firm_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_firm_profiles');
    }
};
