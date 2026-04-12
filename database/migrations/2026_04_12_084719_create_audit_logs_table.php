<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("audit_logs", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->nullable()->constrained()->onDelete("cascade");
            $table->foreignId("user_id")->nullable()->constrained()->onDelete("set null");
            $table->string("action");
            $table->string("model_type")->nullable();
            $table->string("model_id")->nullable();
            $table->text("changes")->nullable(); // JSON representation of before/after
            $table->text("description")->nullable();
            $table->ipAddress()->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp("created_at")->useCurrent();
            $table->timestamp("updated_at")->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("audit_logs");
    }
};

