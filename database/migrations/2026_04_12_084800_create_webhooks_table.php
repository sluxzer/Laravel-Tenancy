<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("webhooks", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->constrained()->onDelete("cascade");
            $table->string("name");
            $table->string("url");
            $table->string("secret");
            $table->json("events")->nullable(); // Array of event names to trigger
            $table->boolean("is_active")->default(true);
            $table->json("headers")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("webhooks");
    }
};

