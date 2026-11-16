<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('savings_goal_id')->nullable()->constrained('savings_goals')->nullOnDelete();
            $table->foreignId('savings_loan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->decimal('amount', 12, 2);
            $table->string('description')->nullable();
            $table->date('movement_date');
            $table->timestamps();

            $table->index(['period_id', 'type']);
            $table->index(['savings_loan_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_movements');
    }
};
