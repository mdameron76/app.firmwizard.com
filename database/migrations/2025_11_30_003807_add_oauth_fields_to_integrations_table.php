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
        Schema::table('integrations', function (Blueprint $table) {
            // Add display_name
            $table->string('display_name')->nullable()->after('name');

            // Add OAuth2 specific fields
            $table->text('access_token')->nullable()->after('credentials');
            $table->text('refresh_token')->nullable()->after('access_token');
            $table->timestamp('token_expires_at')->nullable()->after('refresh_token');
            $table->text('api_key')->nullable()->after('token_expires_at');
            $table->json('settings_json')->nullable()->after('api_key');

            // Add sync status fields
            $table->string('last_sync_status', 50)->nullable()->after('last_synced_at');
            $table->text('last_sync_message')->nullable()->after('last_sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'access_token',
                'refresh_token',
                'token_expires_at',
                'api_key',
                'settings_json',
                'last_sync_status',
                'last_sync_message',
            ]);
        });
    }
};
