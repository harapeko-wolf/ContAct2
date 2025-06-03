<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DocumentFeedbackSeeder extends Seeder
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

        // フィードバックのテストデータを生成
        $feedbacks = [];
        $now = Carbon::now();

        $feedbackTypes = ['very_interested', 'interested', 'not_interested', 'like', 'dislike'];
        $feedbackContents = [
            'very_interested' => ['非常に興味深い内容でした', '詳細な資料をお送りください', 'ぜひ商談をお願いします'],
            'interested' => ['興味があります', '追加情報があればお送りください', '検討させていただきます'],
            'not_interested' => ['今回は見送らせていただきます', '弊社には合わないようです', 'タイミングが合いません'],
            'like' => ['素晴らしい資料です', '分かりやすくて良いです', '参考になりました'],
            'dislike' => ['内容が不十分です', 'もう少し詳細が欲しいです', '分かりにくい部分があります'],
        ];

        foreach ($documentIds as $documentId) {
            // 各ドキュメントに2-8個のフィードバックを作成
            $feedbackCount = rand(2, 8);

            for ($i = 0; $i < $feedbackCount; $i++) {
                // 過去30日間のランダムな日時
                $createdAt = $now->copy()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
                $feedbackType = $feedbackTypes[array_rand($feedbackTypes)];
                $content = $feedbackContents[$feedbackType][array_rand($feedbackContents[$feedbackType])];

                $feedbacks[] = [
                    'id' => Str::uuid(),
                    'document_id' => $documentId,
                    'feedback_type' => $feedbackType,
                    'content' => $content,
                    'feedbacker_ip' => $this->generateRandomIP(),
                    'feedbacker_user_agent' => $this->getRandomUserAgent(),
                    'feedback_metadata' => json_encode([
                        'session_id' => Str::uuid(),
                        'source' => 'web',
                        'rating' => rand(1, 5),
                    ]),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }
        }

        // バッチでデータを挿入
        $chunks = array_chunk($feedbacks, 100);
        foreach ($chunks as $chunk) {
            DB::table('document_feedback')->insert($chunk);
        }

        $this->command->info('DocumentFeedbackSeeder: ' . count($feedbacks) . '件のフィードバックを作成しました。');
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
