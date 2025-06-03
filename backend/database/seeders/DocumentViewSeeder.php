<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DocumentViewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ドキュメントのIDを取得
        $documentIds = DB::table('documents')->pluck('id')->toArray();

        if (empty($documentIds)) {
            $this->command->warn('ドキュメントが見つかりません。DocumentSeederを先に実行してください。');
            return;
        }

        // 閲覧ログのテストデータを生成
        $viewLogs = [];
        $now = Carbon::now();

        foreach ($documentIds as $documentId) {
            // 各ドキュメントに10-30個の閲覧ログを作成
            $viewCount = rand(10, 30);

            for ($i = 0; $i < $viewCount; $i++) {
                // 過去7日間のランダムな日時
                $viewedAt = $now->copy()->subDays(rand(0, 7))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

                $viewLogs[] = [
                    'id' => Str::uuid(),
                    'document_id' => $documentId,
                    'viewer_ip' => $this->generateRandomIP(),
                    'viewer_user_agent' => $this->getRandomUserAgent(),
                    'page_number' => rand(1, 20),
                    'view_duration' => rand(30, 300), // 30秒から5分
                    'viewed_at' => $viewedAt,
                    'viewer_metadata' => json_encode([
                        'session_id' => Str::uuid(),
                        'source' => 'web',
                    ]),
                    'created_at' => $viewedAt,
                    'updated_at' => $viewedAt,
                ];
            }
        }

        // バッチでデータを挿入
        $chunks = array_chunk($viewLogs, 100);
        foreach ($chunks as $chunk) {
            DB::table('document_views')->insert($chunk);
        }

        $this->command->info('DocumentViewSeeder: ' . count($viewLogs) . '件の閲覧ログを作成しました。');
    }

    /**
     * ランダムなIPアドレスを生成
     */
    private function generateRandomIP(): string
    {
        return rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);
    }

    /**
     * ランダムなUser-Agentを取得
     */
    private function getRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Edge/91.0.864.59',
        ];

        return $userAgents[array_rand($userAgents)];
    }
}
