<?php

namespace App\Jobs;

use App\Models\FollowupEmail;
use App\Services\FollowupEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFollowupEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $followupEmailId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $followupEmailId)
    {
        $this->followupEmailId = $followupEmailId;
    }

    /**
     * Execute the job.
     */
    public function handle(FollowupEmailService $followupService): void
    {
        try {
            $followup = FollowupEmail::find($this->followupEmailId);

            if (!$followup) {
                Log::warning('フォローアップメールが見つかりません', [
                    'followup_id' => $this->followupEmailId
                ]);
                return;
            }

            // キャンセル済みまたは送信済みの場合はスキップ
            if ($followup->status !== 'scheduled') {
                Log::info('フォローアップメールがスキップされました', [
                    'followup_id' => $this->followupEmailId,
                    'status' => $followup->status
                ]);
                return;
            }

            // TimeRex予約チェック
            $bookingCheck = $followupService->checkAndCancelForTimeRexBooking($followup->company_id);
            if ($bookingCheck['data']['has_recent_booking'] ?? false) {
                Log::info('TimeRex予約によりフォローアップメールがキャンセルされました', [
                    'followup_id' => $this->followupEmailId,
                    'company_id' => $followup->company_id
                ]);
                return;
            }

            // メール送信処理
            $followupService->sendFollowupEmailById($this->followupEmailId);

            Log::info('フォローアップメールジョブ実行完了', [
                'followup_id' => $this->followupEmailId
            ]);

        } catch (\Exception $e) {
            Log::error('フォローアップメールジョブエラー', [
                'followup_id' => $this->followupEmailId,
                'error' => $e->getMessage()
            ]);

            // フォローアップメールを失敗状態にマーク
            if ($followup = FollowupEmail::find($this->followupEmailId)) {
                $followup->markAsFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('フォローアップメールジョブ失敗', [
            'followup_id' => $this->followupEmailId,
            'error' => $exception->getMessage()
        ]);

        // フォローアップメールを失敗状態にマーク
        if ($followup = FollowupEmail::find($this->followupEmailId)) {
            $followup->markAsFailed($exception->getMessage());
        }
    }
}
