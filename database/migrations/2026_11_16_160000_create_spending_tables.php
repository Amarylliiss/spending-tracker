<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('color', 7)->default('#64748b');
            $table->unsignedBigInteger('monthly_limit')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('category_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('field', 20);
            $table->string('operator', 20);
            $table->string('pattern', 120);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('category_rules')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('spent_on');
            $table->string('merchant', 120)->nullable();
            $table->string('description', 255);
            $table->string('categorized_by', 10)->default('none');
            $table->timestamps();

            $table->index(['user_id', 'spent_on']);
        });

        Schema::create('budget_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->char('month', 7);
            $table->unsignedTinyInteger('level');
            $table->unsignedBigInteger('spent');
            $table->unsignedBigInteger('limit');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_alerts');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('category_rules');
        Schema::dropIfExists('categories');
    }
};
