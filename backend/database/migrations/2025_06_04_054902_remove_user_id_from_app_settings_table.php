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
        // 重複する設定がある場合は、user_id=2のものを残す
        DB::statement('
            DELETE a1 FROM app_settings a1
            INNER JOIN app_settings a2
            WHERE a1.id > a2.id
            AND a1.key = a2.key
            AND a1.user_id != 2
        ');

        // 他のuser_idの設定があれば削除（user_id=2のもの以外）
        DB::table('app_settings')->where('user_id', '!=', 2)->delete();

        // 外部キー制約を先に削除
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // ユニークキーを削除
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'key']);
        });

        // user_idをnullableに変更
        Schema::table('app_settings', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        // user_id=2の設定をuser_id=NULLに変更（システム全体設定として扱う）
        DB::table('app_settings')->where('user_id', 2)->update(['user_id' => null]);

        // user_idカラムを削除
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        // keyにユニークキーを再設定
        Schema::table('app_settings', function (Blueprint $table) {
            $table->unique(['key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // user_idカラムを再追加
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropUnique(['key']);
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
            $table->unique(['user_id', 'key']);
        });
    }
};
