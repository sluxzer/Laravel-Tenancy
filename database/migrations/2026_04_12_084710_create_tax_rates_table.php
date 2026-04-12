<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("tax_rates", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("code")->unique();
            $table->decimal("rate", 5, 4); // e.g., 0.1000 for 10%
            $table->string("type")->default("percentage"); // percentage or fixed
            $table->foreignId("country_id")->nullable(); // Optional: link to countries table if needed
            $table->boolean("is_active")->default(true);
            $table->boolean("is_default")->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("tax_rates");
    }
};

