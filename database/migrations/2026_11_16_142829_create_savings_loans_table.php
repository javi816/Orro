<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_period_id')->constrained('periods')->restrictOnDelete();
            $table->foreignId('due_period_id')->constrained('periods')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_loans');
    }
};
