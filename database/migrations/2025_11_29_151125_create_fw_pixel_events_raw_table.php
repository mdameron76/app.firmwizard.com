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
        Schema::create('fw_pixel_events_raw', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained('firms')->onDelete('cascade');
            $table->string('fwid', 64)->nullable()->comment('FirmWizard visitor ID');
            $table->json('payload_json')->comment('Complete raw payload from pixel');
            $table->string('ip_address', 45)->nullable()->comment('IPv4 or IPv6');
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('firm_id');
            $table->index('fwid');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fw_pixel_events_raw');
    }
};
