<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("analytics_events", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->nullable()->constrained()->onDelete("cascade");
            $table->foreignId("user_id")->nullable()->constrained()->onDelete("set null");
            $table->string("event_name");
            $table->json("properties")->nullable();
            $table->timestamp("occurred_at")->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("analytics_events");
    }
};

