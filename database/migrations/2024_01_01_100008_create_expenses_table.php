<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 20)->unique();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('expense_date');
            $table->date('applied_date');
            $table->string('title');
            $table->bigInteger('total_amount')->default(0);
            $table->enum('status', ['draft','pending','approved','rejected','paid'])->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_item_id')->constrained();
            $table->date('item_date');
            $table->string('description');
            $table->bigInteger('amount');
            $table->string('tax_rate', 3)->default('10');
            $table->string('receipt_path')->nullable();
            $table->integer('sort_order')->default(0);
        });
    }
    public function down(): void { Schema::dropIfExists('expense_items'); Schema::dropIfExists('expenses'); }
};
