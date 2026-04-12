<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("refunds", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->constrained()->onDelete("cascade");
            $table->foreignId("user_id")->nullable()->constrained()->onDelete("set null");
            $table->foreignId("transaction_id")->nullable()->constrained()->onDelete("set null");
            $table->foreignId("invoice_id")->nullable()->constrained()->onDelete("set null");
            $table->decimal("amount", 12, 2);
            $table->string("currency", 3);
            $table->string("reason")->nullable();
            $table->string("status")->default("pending"); // pending, succeeded, failed, cancelled
            $table->text("notes")->nullable();
            $table->foreignId("processed_by")->nullable()->constrained("users")->onDelete("set null");
            $table->date("processed_at")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("refunds");
    }
};

