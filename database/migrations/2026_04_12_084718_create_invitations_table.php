<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("invitations", function (Blueprint $table) {
            $table->id();
            $table->string("email");
            $table->string("token")->unique();
            $table->foreignId("tenant_id")->nullable()->constrained()->onDelete("cascade");
            $table->foreignId("invited_by_user_id")->nullable()->constrained("users")->onDelete("set null");
            $table->foreignId("user_id")->nullable()->constrained("users")->onDelete("cascade");
            $table->foreignId("role_id")->nullable()->constrained("roles");
            $table->string("status")->default("pending"); // pending, accepted, declined, expired
            $table->timestamp("accepted_at")->nullable();
            $table->timestamp("expires_at");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("invitations");
    }
};

