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
        Schema::create('fw_pixel_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained('firms')->onDelete('cascade');
            $table->string('api_key', 128)->unique()->comment('Pixel API key');
            $table->string('domain', 255)->nullable()->comment('Optional: restrict to specific domain');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('firm_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fw_pixel_api_keys');
    }
};
