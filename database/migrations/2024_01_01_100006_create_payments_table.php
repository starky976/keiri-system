<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 20)->unique();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->date('due_date');
            $table->date('payment_date')->nullable();
            $table->bigInteger('amount');
            $table->enum('method', ['bank_transfer','cash','check','credit_card','other'])->default('bank_transfer');
            $table->string('description');
            $table->enum('status', ['pending','approved','paid','cancelled'])->default('pending');
            $table->foreignId('account_item_id')->constrained();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('payments'); }
};
