<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('metric_name');
            $table->integer('threshold_value');
            $table->string('comparison_operator')->default('>'); // >, <, >=, <=, =
            $table->string('type')->default('email'); // email, webhook
            $table->string('webhook_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('trigger_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_alerts');
    }
};
