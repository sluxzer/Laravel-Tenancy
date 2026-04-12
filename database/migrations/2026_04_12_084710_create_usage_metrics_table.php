<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("usage_metrics", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->constrained()->onDelete("cascade");
            $table->string("metric_name");
            $table->integer("value")->default(0);
            $table->date("recorded_at");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("usage_metrics");
    }
};

