<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FollowupEmailService;

class ProcessFollowupEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followup:process-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '送信予定のフォローアップメールを処理する';

    private FollowupEmailService $followupService;

    /**
     * Create a new command instance.
     */
    public function __construct(FollowupEmailService $followupService)
    {
        parent::__construct();
        $this->followupService = $followupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('フォローアップメール処理開始...');

        $result = $this->followupService->processPendingFollowups();

        if ($result['success']) {
            $processed = $result['processed'];
            $sent = $result['sent'];
            $failed = $result['failed'];
            $skipped = $result['skipped'] ?? 0;

            if ($processed > 0) {
                $this->info("処理完了: 処理済み{$processed}件, 送信{$sent}件, 失敗{$failed}件, スキップ{$skipped}件");

                if ($skipped > 0) {
                    $this->comment("スキップ理由: 受注済み会社 または TimeRex予約済み");
                }
            } else {
                $this->comment('送信対象のフォローアップメールはありませんでした');
            }

            // 古いレコードのクリーンアップ（30日以上前のキャンセル済み・送信済み・失敗レコード）
            $this->cleanupOldRecords();
        } else {
            $this->error('処理に失敗しました: ' . $result['message']);
            return 1;
        }

        return 0;
    }

    /**
     * 古いフォローアップメールレコードをクリーンアップ
     */
    private function cleanupOldRecords(): void
    {
        $cutoffDate = now()->subDays(30);

        $deletedCount = \App\Models\FollowupEmail::whereIn('status', ['cancelled', 'sent', 'failed'])
            ->where('updated_at', '<', $cutoffDate)
            ->delete();

        if ($deletedCount > 0) {
            $this->info("古いレコードをクリーンアップしました: {$deletedCount}件");
        }
    }
}
