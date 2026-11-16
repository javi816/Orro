<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->string('type', 10);
            $table->decimal('planned_amount', 12, 2);
            $table->decimal('actual_amount', 12, 2)->nullable();
            $table->date('expense_date')->nullable();
            $table->unsignedSmallInteger('payment_number')->default(1);
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->unique(['period_id', 'expense_template_id', 'payment_number']);
            $table->index(['period_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
