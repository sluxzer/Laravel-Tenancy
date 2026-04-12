<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("usage_pricing", function (Blueprint $table) {
            $table->id();
            $table->string("metric_name");
            $table->string("name");
            $table->decimal("price_per_unit", 12, 2);
            $table->integer("included_units")->default(0);
            $table->json("pricing_tiers")->nullable();
            $table->boolean("is_active")->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("usage_pricing");
    }
};

