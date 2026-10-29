<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 12);
            $table->string('description', 160);
            $table->unsignedBigInteger('amount');
            $table->date('occurred_at');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['occurred_at', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};