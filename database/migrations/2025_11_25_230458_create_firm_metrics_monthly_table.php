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
        Schema::create('firm_metrics_monthly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firm_id')->constrained()->onDelete('cascade');
            $table->string('month'); // YYYY-MM format
            $table->string('metric_type');
            $table->decimal('metric_value', 15, 4)->nullable();
            $table->json('meta_json')->nullable();
            
            $table->unique(['firm_id', 'month', 'metric_type']);
            $table->index('firm_id');
            $table->index('month');
            $table->index('metric_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firm_metrics_monthly');
    }
};
