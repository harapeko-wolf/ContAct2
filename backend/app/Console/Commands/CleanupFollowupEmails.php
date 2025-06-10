<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FollowupEmail;

class CleanupFollowupEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followup:cleanup {--days=30 : 何日前より古いレコードを削除するか} {--dry-run : 実際には削除せず、対象レコード数のみ表示}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '古いフォローアップメールレコードをクリーンアップする';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');

        $cutoffDate = now()->subDays($days);

        $this->info("フォローアップメールクリーンアップ開始");
        $this->info("対象: {$days}日前（{$cutoffDate->format('Y-m-d H:i:s')}）より古いレコード");

        // 対象レコードの検索
        $query = FollowupEmail::whereIn('status', ['cancelled', 'sent', 'failed'])
            ->where('updated_at', '<', $cutoffDate);

        $targetCount = $query->count();

        if ($targetCount === 0) {
            $this->info('削除対象のレコードはありません');
            return 0;
        }

        // 削除対象の詳細を表示
        $this->table(
            ['ステータス', '件数'],
            $query->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->map(function ($item) {
                    return [$item->status, $item->count];
                })
                ->toArray()
        );

        if ($dryRun) {
            $this->warn("【ドライラン】実際には削除しません。削除対象: {$targetCount}件");
            return 0;
        }

        // 確認プロンプト
        if (!$this->confirm("本当に{$targetCount}件のレコードを削除しますか？")) {
            $this->info('クリーンアップをキャンセルしました');
            return 0;
        }

        // 実際の削除
        $deletedCount = $query->delete();

        $this->info("クリーンアップ完了: {$deletedCount}件のレコードを削除しました");

        return 0;
    }
}
