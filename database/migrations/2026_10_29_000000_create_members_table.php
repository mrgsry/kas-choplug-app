<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->date('joined_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->date('paid_at');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->date('month');
            $table->timestamps();
            $table->unique(['member_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_months');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('members');
    }
};