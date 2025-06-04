<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // 設定のキー（例：general.company_name）
            $table->json('value'); // 設定値（JSON形式で柔軟に保存）
            $table->text('description')->nullable(); // 設定項目の説明
            $table->string('type')->default('string'); // データ型（string, number, boolean, array, object）
            $table->boolean('is_public')->default(false); // 公開設定（フロントエンドで使用可能か）
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
