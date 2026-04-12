<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("plans", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("slug")->unique();
            $table->text("description")->nullable();
            $table->decimal("price_monthly", 10, 2);
            $table->decimal("price_yearly", 10, 2)->nullable();
            $table->json("features")->nullable();
            $table->integer("max_users")->default(1);
            $table->integer("max_storage_mb")->nullable();
            $table->boolean("is_active")->default(true);
            $table->integer("sort_order")->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("plans");
    }
};

