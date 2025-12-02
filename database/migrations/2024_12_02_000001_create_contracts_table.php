<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique();
            $table->string('inn')->nullable();
            $table->string('pinfl')->nullable();
            $table->string('passport')->nullable();
            $table->string('company_name');
            $table->string('district');
            $table->enum('status', ['Амал қилувчи', 'Бекор қилинган', 'Якунланган'])->default('Амал қилувчи');
            $table->date('contract_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->decimal('contract_amount', 20, 2)->default(0);
            $table->decimal('initial_payment', 20, 2)->default(0);
            $table->decimal('remaining_amount', 20, 2)->default(0);
            $table->decimal('quarterly_payment', 20, 2)->default(0);
            $table->string('payment_terms')->nullable();
            $table->integer('payment_period')->default(0);
            $table->decimal('advance_percent', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('needs_manual_resolve')->default(false);
            $table->timestamps();

            $table->index(['inn', 'pinfl', 'passport']);
            $table->index('status');
            $table->index('district');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
