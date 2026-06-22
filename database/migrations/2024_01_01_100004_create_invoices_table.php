<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 20)->unique();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('paid_amount')->default(0);
            $table->enum('status', ['draft','sent','paid','overdue','cancelled'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->bigInteger('unit_price');
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->default('式');
            $table->bigInteger('amount');
            $table->string('tax_rate', 3)->default('10');
            $table->integer('sort_order')->default(0);
        });
    }
    public function down(): void { Schema::dropIfExists('invoice_items'); Schema::dropIfExists('invoices'); }
};
