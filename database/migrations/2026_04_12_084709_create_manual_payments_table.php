<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("manual_payments", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->constrained()->onDelete("cascade");
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table->foreignId("invoice_id")->nullable()->constrained()->onDelete("set null");
            $table->string("method"); // bank_transfer, check, cash, other
            $table->string("reference")->nullable();
            $table->decimal("amount", 12, 2);
            $table->string("currency", 3);
            $table->string("status")->default("pending"); // pending, approved, rejected
            $table->text("notes")->nullable();
            $table->date("processed_at")->nullable();
            $table->foreignId("processed_by")->nullable()->constrained("users")->onDelete("set null");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("manual_payments");
    }
};

