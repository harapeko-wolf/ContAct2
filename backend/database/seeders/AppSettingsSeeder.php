<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 一般設定
        AppSetting::updateOrCreate(
            ['key' => 'general.default_expiration'],
            [
                'value' => 30,
                'description' => 'リンク有効期限（日数）',
                'type' => 'number',
                'is_public' => false
            ]
        );

        AppSetting::updateOrCreate(
            ['key' => 'general.track_page_views'],
            [
                'value' => true,
                'description' => 'ページビュー追跡',
                'type' => 'boolean',
                'is_public' => false
            ]
        );

        AppSetting::updateOrCreate(
            ['key' => 'general.require_survey'],
            [
                'value' => true,
                'description' => '閲覧前のアンケート要求',
                'type' => 'boolean',
                'is_public' => true
            ]
        );

        AppSetting::updateOrCreate(
            ['key' => 'general.show_booking_option'],
            [
                'value' => true,
                'description' => 'ミーティング予約オプション表示',
                'type' => 'boolean',
                'is_public' => true
            ]
        );

        // アンケート設定
        AppSetting::updateOrCreate(
            ['key' => 'survey.title'],
            [
                'value' => '資料をご覧になる前に',
                'description' => 'アンケートタイトル',
                'type' => 'string',
                'is_public' => true
            ]
        );

        AppSetting::updateOrCreate(
            ['key' => 'survey.description'],
            [
                'value' => '現在の興味度をお聞かせください',
                'description' => 'アンケート説明',
                'type' => 'string',
                'is_public' => true
            ]
        );

        AppSetting::updateOrCreate(
            ['key' => 'survey.options'],
            [
                'value' => [
                    ['id' => 1, 'label' => '非常に興味がある'],
                    ['id' => 2, 'label' => 'やや興味がある'],
                    ['id' => 3, 'label' => '詳しい情報が必要'],
                    ['id' => 4, 'label' => '興味なし'],
                ],
                'description' => 'アンケート選択肢',
                'type' => 'array',
                'is_public' => true
            ]
        );

        // スコアリング設定
        AppSetting::updateOrCreate(
            ['key' => 'scoring.time_threshold'],
            [
                'value' => 5,
                'description' => '最小閲覧時間（秒）',
                'type' => 'number',
                'is_public' => false
            ]
        );

        AppSetting::updateOrCreate(
            ['key' => 'scoring.completion_bonus'],
            [
                'value' => 20,
                'description' => '完了ボーナスポイント',
                'type' => 'number',
                'is_public' => false
            ]
        );

        AppSetting::updateOrCreate(
            ['key' => 'scoring.tiers'],
            [
                'value' => [
                    ['timeThreshold' => 10, 'points' => 1],
                    ['timeThreshold' => 30, 'points' => 3],
                    ['timeThreshold' => 60, 'points' => 5],
                ],
                'description' => 'スコアリング層',
                'type' => 'array',
                'is_public' => false
            ]
        );

        $this->command->info("アプリケーション設定のシードが完了しました。");
    }
}
