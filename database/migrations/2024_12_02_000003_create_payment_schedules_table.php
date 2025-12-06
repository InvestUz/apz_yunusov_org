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
            $table->integer('month');
            $table->date('period_date'); // The exact date from CSV header (e.g., 30.04.24)
            $table->string('period_label'); // Human-readable label (e.g., "2024-04")
            $table->decimal('planned_amount', 20, 2)->default(0);
            $table->decimal('actual_amount', 20, 2)->default(0);
            $table->decimal('debt_amount', 20, 2)->default(0);
            $table->boolean('is_overdue')->default(false);
            $table->timestamps();

            $table->index(['contract_id', 'year', 'month']);
            $table->index('period_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_schedules');
    }
};
