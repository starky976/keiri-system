<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('部門コード（例: D001）');
            $table->string('name')->comment('部門名');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // journals テーブルに department_id を追加
        Schema::table('journals', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('journals', fn($t) => $t->dropForeign(['department_id']));
        Schema::table('journals', fn($t) => $t->dropColumn('department_id'));
        Schema::dropIfExists('departments');
    }
};
