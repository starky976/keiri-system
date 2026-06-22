<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number', 20)->unique();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->date('sale_date');
            $table->string('description');
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->string('tax_rate', 3)->default('10');
            $table->enum('status', ['pending','invoiced','paid'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->bigInteger('unit_price');
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->default('式');
            $table->bigInteger('amount');
            $table->integer('sort_order')->default(0);
        });
    }
    public function down(): void { Schema::dropIfExists('sale_items'); Schema::dropIfExists('sales'); }
};
