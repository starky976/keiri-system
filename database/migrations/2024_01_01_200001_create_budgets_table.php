<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year')->comment('年度（例: 2024）');
            $table->unsignedTinyInteger('month')->nullable()->comment('月（null = 年次予算）');
            $table->foreignId('account_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2)->default(0)->comment('予算額');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['fiscal_year', 'month', 'account_item_id', 'department_id'], 'budgets_unique');
            $table->index(['fiscal_year', 'month']);
        });
    }
    public function down(): void { Schema::dropIfExists('budgets'); }
};
