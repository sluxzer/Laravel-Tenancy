<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("scheduled_reports", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->constrained()->onDelete("cascade");
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table->foreignId("report_template_id")->constrained()->onDelete("cascade");
            $table->string("name");
            $table->string("frequency"); // daily, weekly, monthly, quarterly, yearly, custom
            $table->json("schedule_config")->nullable(); // Cron expression or date
            $table->json("recipients")->nullable();
            $table->boolean("is_active")->default(true);
            $table->timestamp("last_run_at")->nullable();
            $table->timestamp("next_run_at")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("scheduled_reports");
    }
};

