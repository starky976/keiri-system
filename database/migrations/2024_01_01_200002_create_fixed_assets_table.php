<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_number', 20)->unique()->comment('資産番号（A+YYYYMMDD+連番）');
            $table->string('name')->comment('資産名称');
            $table->string('category')->comment('種別: building/vehicle/equipment/software 等');
            $table->date('acquisition_date')->comment('取得日');
            $table->decimal('acquisition_amount', 15, 2)->comment('取得価額');
            $table->unsignedSmallInteger('useful_life')->comment('耐用年数（年）');
            $table->enum('depreciation_method', ['straight_line', 'declining_balance'])->default('straight_line')->comment('定額法/定率法');
            $table->decimal('residual_value', 15, 2)->default(1)->comment('残存価額');
            $table->date('disposal_date')->nullable()->comment('廃棄・売却日');
            $table->decimal('disposal_amount', 15, 2)->nullable()->comment('売却額');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['category', 'acquisition_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('fixed_assets'); }
};
