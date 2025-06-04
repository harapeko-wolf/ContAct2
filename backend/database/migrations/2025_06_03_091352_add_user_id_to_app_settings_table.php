<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            // user_idカラムを追加（nullable）
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });

        // 既存の設定をuser_id=2に関連付け
        DB::table('app_settings')->whereNull('user_id')->update(['user_id' => 2]);

        // user_idをnon-nullableに変更
        Schema::table('app_settings', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });

        // ユニークキーをuser_id + keyの組み合わせに変更
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->unique(['user_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            // ユニークキーを元に戻す
            $table->dropUnique(['user_id', 'key']);
            $table->unique(['key']);

            // user_idカラムを削除
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
