<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("webhook_events", function (Blueprint $table) {
            $table->id();
            $table->foreignId("webhook_id")->constrained()->onDelete("cascade");
            $table->string("event_name");
            $table->json("payload");
            $table->integer("status_code")->nullable();
            $table->text("response")->nullable();
            $table->string("status")->default("pending"); // pending, delivered, failed, retried
            $table->integer("retry_count")->default(0);
            $table->timestamp("delivered_at")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("webhook_events");
    }
};

