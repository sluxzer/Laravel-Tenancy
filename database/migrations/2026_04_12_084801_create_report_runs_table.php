<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('set null');
            $table->foreignId('report_template_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('custom_report_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->json('parameters')->nullable();
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->integer('total_rows')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_runs');
    }
};
