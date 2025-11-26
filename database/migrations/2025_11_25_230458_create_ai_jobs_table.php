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
        Schema::create('ai_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->onDelete('cascade');
            $table->string('job_type'); // blog_post, competitor_analysis, website_analysis, gbp_analysis, google_ads, lsa_ads, meta_ads, backlinks, analytics, firm_profile
            $table->longText('input_json')->nullable();
            $table->longText('output_json')->nullable();
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('firm_id');
            $table->index('job_type');
            $table->index('status');
            $table->index(['firm_id', 'job_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_jobs');
    }
};
