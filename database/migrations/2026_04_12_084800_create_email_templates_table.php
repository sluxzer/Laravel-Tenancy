<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("email_templates", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->nullable()->constrained()->onDelete("cascade");
            $table->string("slug")->unique();
            $table->string("name");
            $table->text("subject");
            $table->longText("html_content");
            $table->longText("text_content")->nullable();
            $table->json("variables")->nullable();
            $table->boolean("is_system")->default(false);
            $table->boolean("is_enabled")->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("email_templates");
    }
};

