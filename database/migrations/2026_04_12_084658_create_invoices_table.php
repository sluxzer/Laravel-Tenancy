<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("invoices", function (Blueprint $table) {
            $table->id();
            $table->string("number")->unique();
            $table->foreignId("tenant_id")->constrained()->onDelete("cascade");
            $table->foreignId("user_id")->nullable()->constrained()->onDelete("set null");
            $table->foreignId("subscription_id")->nullable()->constrained()->onDelete("set null");
            $table->decimal("subtotal", 12, 2)->default(0);
            $table->decimal("tax_amount", 12, 2)->default(0);
            $table->decimal("discount_amount", 12, 2)->default(0);
            $table->decimal("total_amount", 12, 2)->default(0);
            $table->string("currency", 3);
            $table->string("status")->default("pending"); // pending, paid, overdue, cancelled
            $table->date("due_date");
            $table->date("paid_at")->nullable();
            $table->date("cancelled_at")->nullable();
            $table->text("notes")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("invoices");
    }
};

