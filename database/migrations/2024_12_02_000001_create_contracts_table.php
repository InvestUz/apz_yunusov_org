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
            $table->string('additional_contract_number')->nullable();
            $table->string('inn')->nullable();
            $table->string('pinfl')->nullable();
            $table->string('company_name');
            $table->string('district');
            $table->enum('status', ['амал қилувчи', 'Бекор қилинган', 'Якунланган'])->default('амал қилувчи');
            $table->date('contract_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->string('payment_terms')->nullable();
            $table->integer('payment_period')->default(0);
            $table->string('advance_percent')->nullable();
            $table->decimal('contract_amount', 20, 2)->default(0);
            $table->decimal('one_time_payment', 20, 2)->default(0);
            $table->decimal('monthly_payment', 20, 2)->default(0);
            $table->decimal('total_payment', 20, 2)->default(0);
            $table->decimal('remaining_amount', 20, 2)->default(0);
            $table->decimal('total_fact', 20, 2)->default(0);
            $table->decimal('total_plan', 20, 2)->default(0);
            $table->timestamps();

            $table->index(['inn', 'pinfl']);
            $table->index('status');
            $table->index('district');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
