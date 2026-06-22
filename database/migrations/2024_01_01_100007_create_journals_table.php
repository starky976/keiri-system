<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('journal_number', 20)->unique();
            $table->date('journal_date');
            $table->string('description');
            $table->enum('source_type', ['manual','sale','invoice','receipt','payment','expense'])->default('manual');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->enum('side', ['debit','credit']);
            $table->foreignId('account_item_id')->constrained();
            $table->bigInteger('amount');
            $table->string('description')->nullable();
            $table->integer('sort_order')->default(0);
        });
    }
    public function down(): void { Schema::dropIfExists('journal_entries'); Schema::dropIfExists('journals'); }
};
