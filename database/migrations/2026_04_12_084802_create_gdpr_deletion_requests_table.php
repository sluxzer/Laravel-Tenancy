<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("gdpr_deletion_requests", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->nullable()->constrained()->onDelete("cascade");
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table->string("type"); // full, partial, specific
            $table->json("requested_entities")->nullable();
            $table->text("reason")->nullable();
            $table->string("status")->default("pending"); // pending, processing, completed, rejected
            $table->foreignId("processed_by")->nullable()->constrained("users")->onDelete("set null");
            $table->timestamp("processed_at")->nullable();
            $table->text("admin_notes")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("gdpr_deletion_requests");
    }
};

