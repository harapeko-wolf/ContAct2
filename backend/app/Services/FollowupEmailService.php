<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Company;
use App\Models\Document;
use App\Models\FollowupEmail;
use App\Mail\FollowupEmail as FollowupEmailMail;
use App\Jobs\SendFollowupEmailJob;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FollowupEmailService
{
    /**
     * フォローアップタイマーを開始
     */
    public function startFollowupTimer(string $companyId, string $documentId, string $viewerIp): array
    {
        try {
            // 設定値取得
            $isEnabled = AppSetting::get('email.followup_enabled', true);
            if (!$isEnabled) {
                return [
                    'success' => false,
                    'message' => 'フォローアップメール機能が無効になっています'
                ];
            }

            $delayMinutes = AppSetting::get('email.followup_delay_minutes', 15);

            // 会社とドキュメントの存在確認
            $company = Company::find($companyId);
            if (!$company) {
                return [
                    'success' => false,
                    'message' => '会社が見つかりません'
                ];
            }

            if (!$company->email) {
                return [
                    'success' => false,
                    'message' => '会社のメールアドレスが設定されていません'
                ];
            }

            // 会社ステータスが「受注」の場合はフォローアップメール不要
            if ($company->status === 'active') {
                return [
                    'success' => false,
                    'message' => '受注済みの会社にはフォローアップメールを送信しません'
                ];
            }

            $document = Document::find($documentId);
            if (!$document) {
                return [
                    'success' => false,
                    'message' => 'ドキュメントが見つかりません'
                ];
            }

            $scheduledFor = now()->addMinutes($delayMinutes);

            // 既存のscheduledレコードを探す
            $existingFollowup = FollowupEmail::where('company_id', $companyId)
                ->where('document_id', $documentId)
                ->where('viewer_ip', $viewerIp)
                ->where('status', 'scheduled')
                ->first();

            if ($existingFollowup) {
                // 既存レコードを更新（UPSERT方式）
                $existingFollowup->update([
                    'triggered_at' => now(),
                    'scheduled_for' => $scheduledFor,
                    'status' => 'scheduled',
                    'cancellation_reason' => null,
                    'error_message' => null,
                ]);
                $followup = $existingFollowup;
                $action = 'updated';
            } else {
                // 新規レコード作成
                $followup = FollowupEmail::create([
                    'company_id' => $companyId,
                    'document_id' => $documentId,
                    'viewer_ip' => $viewerIp,
                    'triggered_at' => now(),
                    'scheduled_for' => $scheduledFor,
                    'status' => 'scheduled',
                ]);
                $action = 'created';
            }

            Log::info('フォローアップタイマー開始', [
                'followup_id' => $followup->id,
                'company_id' => $companyId,
                'document_id' => $documentId,
                'viewer_ip' => $viewerIp,
                'scheduled_for' => $scheduledFor,
                'action' => $action,
            ]);

            return [
                'success' => true,
                'message' => 'フォローアップタイマーを開始しました',
                'data' => [
                    'followup_id' => $followup->id,
                    'scheduled_for' => $scheduledFor,
                    'delay_minutes' => $delayMinutes,
                    'action' => $action,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('フォローアップタイマー開始エラー', [
                'error' => $e->getMessage(),
                'company_id' => $companyId,
                'document_id' => $documentId,
                'viewer_ip' => $viewerIp,
            ]);

            return [
                'success' => false,
                'message' => 'フォローアップタイマーの開始に失敗しました'
            ];
        }
    }

    /**
     * フォローアップタイマーを停止
     */
    public function stopFollowupTimer(string $companyId, string $documentId, string $viewerIp, string $reason = 'user_cancelled'): array
    {
        try {
            $cancelled = $this->cancelExistingFollowup($companyId, $documentId, $viewerIp, $reason);

            Log::info('フォローアップタイマー停止', [
                'company_id' => $companyId,
                'document_id' => $documentId,
                'viewer_ip' => $viewerIp,
                'reason' => $reason,
                'cancelled_count' => $cancelled,
            ]);

            return [
                'success' => true,
                'message' => 'フォローアップタイマーを停止しました',
                'data' => [
                    'cancelled_count' => $cancelled,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('フォローアップタイマー停止エラー', [
                'error' => $e->getMessage(),
                'company_id' => $companyId,
                'document_id' => $documentId,
                'viewer_ip' => $viewerIp,
            ]);

            return [
                'success' => false,
                'message' => 'フォローアップタイマーの停止に失敗しました'
            ];
        }
    }

    /**
     * 既存のフォローアップメールをキャンセル
     */
    private function cancelExistingFollowup(string $companyId, string $documentId, string $viewerIp, string $reason): int
    {
        $existingFollowups = FollowupEmail::where('company_id', $companyId)
            ->where('document_id', $documentId)
            ->where('viewer_ip', $viewerIp)
            ->where('status', 'scheduled')
            ->get();

        $cancelledCount = 0;
        foreach ($existingFollowups as $followup) {
            $followup->cancel($reason);
            $cancelledCount++;
        }

        return $cancelledCount;
    }

    /**
     * TimeRex予約をチェックして、予約済みの場合はフォローアップメールをキャンセル
     */
    public function checkAndCancelForTimeRexBooking(string $companyId): array
    {
        try {
            $company = Company::find($companyId);
            if (!$company) {
                return [
                    'success' => false,
                    'message' => '会社が見つかりません'
                ];
            }

            // 最近の予約を確認
            $bookings = $company->timerex_bookings;
            if (!$bookings || empty($bookings['bookings'])) {
                return [
                    'success' => true,
                    'message' => '予約がありません',
                    'data' => ['has_recent_booking' => false]
                ];
            }

            // 最新の予約を取得
            $latestBooking = collect($bookings['bookings'])
                ->sortByDesc('created_at')
                ->first();

            if (!$latestBooking || $latestBooking['status'] !== 'confirmed') {
                return [
                    'success' => true,
                    'message' => '確定済み予約がありません',
                    'data' => ['has_recent_booking' => false]
                ];
            }

            // 最近（30分以内）の予約があるかチェック
            $bookingTime = Carbon::parse($latestBooking['created_at']);
            if ($bookingTime->diffInMinutes(now()) <= 30) {
                // 該当会社のスケジュール済みフォローアップメールをキャンセル
                $cancelledCount = FollowupEmail::where('company_id', $companyId)
                    ->where('status', 'scheduled')
                    ->get()
                    ->each(function ($followup) {
                        $followup->cancel('timerex_booking_confirmed');
                    })
                    ->count();

                Log::info('TimeRex予約によりフォローアップメールをキャンセル', [
                    'company_id' => $companyId,
                    'booking_time' => $bookingTime,
                    'cancelled_count' => $cancelledCount,
                ]);

                return [
                    'success' => true,
                    'message' => 'TimeRex予約によりフォローアップメールをキャンセルしました',
                    'data' => [
                        'has_recent_booking' => true,
                        'cancelled_count' => $cancelledCount,
                    ]
                ];
            }

            return [
                'success' => true,
                'message' => '最近の予約はありません',
                'data' => ['has_recent_booking' => false]
            ];

        } catch (\Exception $e) {
            Log::error('TimeRex予約チェックエラー', [
                'error' => $e->getMessage(),
                'company_id' => $companyId,
            ]);

            return [
                'success' => false,
                'message' => 'TimeRex予約チェックに失敗しました'
            ];
        }
    }

    /**
     * 送信予定のフォローアップメールを処理
     */
    public function processPendingFollowups(): array
    {
        $processed = 0;
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        try {
            // N+1問題を回避するため、companyリレーションをeager loading
            $followups = FollowupEmail::getScheduledEmails()->load('company');

            foreach ($followups as $followup) {
                $processed++;

                try {
                    // 会社ステータスチェック（受注済みならスキップ）
                    if ($followup->company->status === 'active') {
                        $followup->cancel('company_status_received_order');
                        $skipped++;

                        Log::info('受注済み会社のためフォローアップメールをスキップ', [
                            'followup_id' => $followup->id,
                            'company_id' => $followup->company_id,
                            'company_status' => $followup->company->status,
                        ]);
                        continue;
                    }

                    // TimeRex予約チェック
                    $bookingCheck = $this->checkAndCancelForTimeRexBooking($followup->company_id);
                    if ($bookingCheck['data']['has_recent_booking'] ?? false) {
                        $skipped++;
                        continue; // 予約済みの場合はスキップ（既にキャンセル済み）
                    }

                    // メール送信
                    $this->sendFollowupEmail($followup);
                    $sent++;

                } catch (\Exception $e) {
                    Log::error('フォローアップメール送信エラー', [
                        'followup_id' => $followup->id,
                        'error' => $e->getMessage(),
                    ]);

                    $followup->markAsFailed($e->getMessage());
                    $failed++;
                }
            }

            Log::info('フォローアップメール処理完了', [
                'processed' => $processed,
                'sent' => $sent,
                'failed' => $failed,
                'skipped' => $skipped,
            ]);

            return [
                'success' => true,
                'processed' => $processed,
                'sent' => $sent,
                'failed' => $failed,
                'skipped' => $skipped,
            ];

        } catch (\Exception $e) {
            Log::error('フォローアップメール処理エラー', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'フォローアップメール処理に失敗しました',
            ];
        }
    }

    /**
     * フォローアップメールを送信
     */
    private function sendFollowupEmail(FollowupEmail $followup): void
    {
        $company = $followup->company;
        $document = $followup->document;

        if (!$company->email) {
            throw new \Exception('会社のメールアドレスが設定されていません');
        }

        $subject = AppSetting::get('email.followup_subject', '資料のご確認ありがとうございました - さらに詳しくご説明いたします');

        Mail::to($company->email)->send(new FollowupEmailMail($company, $document, $subject));

        $followup->markAsSent();

        Log::info('フォローアップメール送信完了', [
            'followup_id' => $followup->id,
            'company_id' => $company->id,
            'document_id' => $document->id,
            'to_email' => $company->email,
        ]);
    }

    /**
     * IDでフォローアップメールを送信
     */
    public function sendFollowupEmailById(string $followupEmailId): void
    {
        $followup = FollowupEmail::find($followupEmailId);

        if (!$followup) {
            throw new \Exception('フォローアップメールが見つかりません');
        }

        $this->sendFollowupEmail($followup);
    }
}
