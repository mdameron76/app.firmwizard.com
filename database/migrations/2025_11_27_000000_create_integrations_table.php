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
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->onDelete('cascade');
            $table->string('platform'); // wordpress, google_ads, meta_ads, gbp, etc.
            $table->string('name'); // User-friendly name e.g., "Main WordPress Site"
            $table->string('auth_type'); // bearer_token, oauth2, jwt, openid
            $table->text('credentials'); // Encrypted JSON with tokens, secrets, etc.
            $table->json('config')->nullable(); // Platform-specific config (site URL, account IDs, etc.)
            $table->string('status')->default('active'); // active, inactive, error
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['firm_id', 'platform']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
