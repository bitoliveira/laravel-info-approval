<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable'); // modelo alvo (Employee, Invoice, etc.)
            $table->string('action'); // ex: update_field, delete, create
            $table->json('data')->nullable(); // dados da alteração
            // Níveis de aprovação e registo de aprovações por nível
            $table->json('levels')->nullable(); // [{ roles: ["Manager", "Finance"] }, { roles: ["Admin"] }]
            $table->unsignedInteger('current_level')->default(1); // 1-based
            $table->json('approvals_log')->nullable(); // histórico de aprovações/rejeições
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
