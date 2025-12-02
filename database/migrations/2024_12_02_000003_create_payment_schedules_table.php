<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('quarter')->nullable();
            $table->string('period'); // "2024 Q1", "2025", etc.
            $table->decimal('planned_amount', 20, 2)->default(0);
            $table->decimal('actual_amount', 20, 2)->default(0);
            $table->decimal('debt_amount', 20, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->boolean('is_overdue')->default(false);
            $table->timestamps();
            
            $table->index(['contract_id', 'year', 'quarter']);
            $table->index('period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_schedules');
    }
};
