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
        Schema::create('ai_firm_profile_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->onDelete('cascade');
            $table->foreignId('ai_firm_profile_id')->constrained()->onDelete('cascade');
            $table->longText('profile_json');
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('firm_id');
            $table->index('ai_firm_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_firm_profile_versions');
    }
};
