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
        Schema::create('ai_attribution_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->nullable()->constrained('firms')->onDelete('cascade');
            $table->string('fwid', 64)->nullable()->comment('FirmWizard visitor ID');
            $table->string('gclid', 255)->nullable()->comment('Google Click ID');
            $table->string('fbclid', 255)->nullable()->comment('Facebook Click ID');
            $table->string('utm_source', 255)->nullable();
            $table->string('utm_medium', 255)->nullable();
            $table->string('utm_campaign', 255)->nullable();
            $table->text('url')->nullable()->comment('Landing page URL');
            $table->text('referrer')->nullable()->comment('Referrer URL');
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable()->comment('IPv4 or IPv6');
            $table->foreignId('crm_lead_id')->nullable()->comment('Link to CRM lead');
            $table->foreignId('crm_case_id')->nullable()->comment('Link to CRM case');
            $table->string('matched_with', 50)->nullable()->comment('How event was matched to lead');
            $table->string('event_type', 64)->nullable()->default('pageview')->comment('pageview, gbp_click, ad_click, form_view, etc.');
            $table->timestamps();

            $table->index('firm_id');
            $table->index('fwid');
            $table->index('gclid');
            $table->index('fbclid');
            $table->index('event_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_attribution_events');
    }
};
