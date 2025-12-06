<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');
            $table->date('payment_date');
            $table->string('inn')->nullable();
            $table->string('pinfl')->nullable();
            $table->decimal('amount_credit', 20, 2)->default(0);
            $table->decimal('amount_debit', 20, 2)->default(0);
            $table->string('district')->nullable();
            $table->text('description')->nullable();
            $table->string('payment_type')->nullable();
            $table->integer('year')->nullable();
            $table->string('month')->nullable();
            $table->boolean('is_matched')->default(false);
            $table->timestamps();

            $table->index(['inn', 'pinfl']);
            $table->index('payment_date');
            $table->index('contract_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
