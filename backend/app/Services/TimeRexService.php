<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class TimeRexService
{
    /**
     * TimeRex Webhookを処理
     */
    public function processWebhook(array $payload): array
    {
        try {
            Log::info('TimeRex Webhook受信開始', ['webhook_type' => $payload['webhook_type'] ?? 'unknown']);

            // ペイロード検証
            $this->validatePayload($payload);

            // 会社IDを特定
            $companyId = $this->extractCompanyId($payload);

            if (!$companyId) {
                throw new Exception('会社IDが特定できませんでした');
            }

            // 会社を取得
            $company = Company::find($companyId);
            if (!$company) {
                throw new Exception("会社が見つかりません: {$companyId}");
            }

            // 予約データを構築
            $bookingData = $this->buildBookingData($payload);

            // 予約データを保存
            DB::transaction(function () use ($company, $bookingData) {
                $company->addTimeRexBooking($bookingData);
            });

            Log::info('TimeRex Webhook処理完了', [
                'company_id' => $companyId,
                'event_id' => $bookingData['event_id'],
                'status' => $bookingData['status']
            ]);

            return [
                'success' => true,
                'message' => 'Webhook処理が完了しました',
                'data' => [
                    'company_id' => $companyId,
                    'event_id' => $bookingData['event_id'],
                    'status' => $bookingData['status']
                ]
            ];

        } catch (Exception $e) {
            Log::error('TimeRex Webhook処理エラー', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * ペイロード検証
     */
    private function validatePayload(array $payload): void
    {
        $requiredFields = ['webhook_type', 'event'];

        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                throw new Exception("必須フィールドが不足: {$field}");
            }
        }

        $allowedTypes = ['event_confirmed', 'event_cancelled'];
        if (!in_array($payload['webhook_type'], $allowedTypes)) {
            throw new Exception("不正なwebhook_type: {$payload['webhook_type']}");
        }

        if (!isset($payload['event']['id'])) {
            throw new Exception('イベントIDが不足しています');
        }
    }

    /**
     * 会社IDを抽出
     */
    private function extractCompanyId(array $payload): ?string
    {
        // 1. URLパラメータから取得を試行
        if (isset($payload['calendar_url'])) {
            $companyId = $this->extractCompanyIdFromUrl($payload['calendar_url']);
            if ($companyId) {
                return $companyId;
            }
        }

        // 2. guest_commentから取得を試行（フリープラン対応）
        $forms = $payload['event']['form'] ?? [];
        foreach ($forms as $form) {
            if ($form['field_type'] === 'guest_comment' && !empty($form['value'])) {
                // UUIDフォーマットの簡単な検証
                if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $form['value'])) {
                    return $form['value'];
                }
            }
        }

        return null;
    }

    /**
     * URLから会社IDを抽出
     */
    private function extractCompanyIdFromUrl(string $url): ?string
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $params);
            return $params['company_id'] ?? null;
        }
        return null;
    }

    /**
     * ドキュメントIDを抽出
     */
    private function extractDocumentId(array $payload): ?string
    {
        if (isset($payload['calendar_url'])) {
            $query = parse_url($payload['calendar_url'], PHP_URL_QUERY);
            if ($query) {
                parse_str($query, $params);
                return $params['document_id'] ?? null;
            }
        }
        return null;
    }

    /**
     * 予約データを構築
     */
    private function buildBookingData(array $payload): array
    {
        $event = $payload['event'];
        $webhookType = $payload['webhook_type'];

        // フォームデータを抽出
        $formData = $this->extractFormData($event['form'] ?? []);

        // ステータス決定
        $status = $webhookType === 'event_confirmed' ? 'confirmed' : 'cancelled';

        $bookingData = [
            'event_id' => $event['id'],
            'status' => $status,
            'start_datetime' => $event['start_datetime'],
            'end_datetime' => $event['end_datetime'],
            'local_start_datetime' => $event['local_start_datetime'] ?? null,
            'local_end_datetime' => $event['local_end_datetime'] ?? null,
            'guest_name' => $formData['guest_name'] ?? null,
            'guest_email' => $formData['guest_email'] ?? null,
            'company_name' => $formData['company_name'] ?? null,
            'guest_comment' => $formData['guest_comment'] ?? null,
            'calendar_name' => $payload['calendar_name'] ?? null,
            'document_id' => $this->extractDocumentId($payload),
            'created_at' => $event['created_at'] ?? now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];

        // キャンセル時は追加情報
        if ($status === 'cancelled') {
            $bookingData['canceled_at'] = $event['canceled_at'] ?? now()->toISOString();
            $bookingData['cancellation_reason'] = $event['cancellation_reason'] ?? null;
        }

        return $bookingData;
    }

    /**
     * フォームデータを抽出
     */
    private function extractFormData(array $forms): array
    {
        $formData = [];

        foreach ($forms as $form) {
            $fieldType = $form['field_type'] ?? null;
            $value = $form['value'] ?? null;

            if ($fieldType && $value !== null) {
                $formData[$fieldType] = $value;
            }
        }

        return $formData;
    }

    /**
     * Webhook認証トークンを検証
     */
    public function validateAuthToken(string $token): bool
    {
        $expectedToken = config('services.timerex.webhook_token');

        if (empty($expectedToken)) {
            Log::warning('TimeRex Webhook認証トークンが設定されていません');
            return false;
        }

        return hash_equals($expectedToken, $token);
    }
}
