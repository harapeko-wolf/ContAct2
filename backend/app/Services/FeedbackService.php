<?php

namespace App\Services;

use App\Models\DocumentFeedback;
use App\Models\Document;
use Illuminate\Support\Facades\Log;

class FeedbackService
{
    /**
     * フィードバックを送信
     */
    public function submitFeedback(string $companyId, string $documentId, array $data, string $feedbackerIp, ?string $userAgent = null): DocumentFeedback
    {
        try {
            // ドキュメントの存在確認
            $document = Document::where('company_id', $companyId)
                ->where('id', $documentId)
                ->firstOrFail();

            // フィードバックを作成
            $feedback = DocumentFeedback::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'document_id' => $documentId,
                'feedback_type' => $data['feedback_type'],
                'content' => $data['content'] ?? null,
                'feedbacker_ip' => $feedbackerIp,
                'feedbacker_user_agent' => $userAgent,
                'feedback_metadata' => $data['metadata'] ?? null,
            ]);

            return $feedback;

        } catch (\Exception $e) {
            Log::error('フィードバック送信エラー: ' . $e->getMessage(), [
                'company_id' => $companyId,
                'document_id' => $documentId,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * ドキュメントのフィードバック一覧を取得
     */
    public function getDocumentFeedback(string $documentId, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator
    {
        try {
            return DocumentFeedback::where('document_id', $documentId)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

        } catch (\Exception $e) {
            Log::error('ドキュメントフィードバック取得エラー: ' . $e->getMessage(), ['document_id' => $documentId]);
            throw $e;
        }
    }

    /**
     * 会社全体のフィードバックを取得
     */
    public function getCompanyFeedback(string $companyId, int $perPage = 50): \Illuminate\Pagination\LengthAwarePaginator
    {
        try {
            return DocumentFeedback::select('document_feedback.*', 'documents.title', 'documents.file_name')
                ->join('documents', 'document_feedback.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->orderBy('document_feedback.created_at', 'desc')
                ->paginate($perPage);

        } catch (\Exception $e) {
            Log::error('会社フィードバック取得エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            throw $e;
        }
    }

    /**
     * フィードバック統計情報を取得
     */
    public function getFeedbackStatistics(string $companyId): array
    {
        try {
            $stats = DocumentFeedback::select([
                    \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_feedback'),
                    \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT feedbacker_ip) as unique_feedbackers'),
                    \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT document_id) as documents_with_feedback')
                ])
                ->join('documents', 'document_feedback.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->first();

            // フィードバックタイプ別統計
            $typeStats = DocumentFeedback::select([
                    'feedback_type',
                    \Illuminate\Support\Facades\DB::raw('COUNT(*) as count')
                ])
                ->join('documents', 'document_feedback.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->groupBy('feedback_type')
                ->get();

            // 日別フィードバック統計（過去30日）
            $dailyStats = DocumentFeedback::select([
                    \Illuminate\Support\Facades\DB::raw('DATE(document_feedback.created_at) as date'),
                    \Illuminate\Support\Facades\DB::raw('COUNT(*) as feedback_count')
                ])
                ->join('documents', 'document_feedback.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->where('document_feedback.created_at', '>=', now()->subDays(30))
                ->groupBy(\Illuminate\Support\Facades\DB::raw('DATE(document_feedback.created_at)'))
                ->orderBy('date', 'desc')
                ->get();

            // アンケートスコア集計
            $surveyScores = $this->calculateSurveyScores($companyId);

            return [
                'total_feedback' => $stats->total_feedback ?? 0,
                'unique_feedbackers' => $stats->unique_feedbackers ?? 0,
                'documents_with_feedback' => $stats->documents_with_feedback ?? 0,
                'type_stats' => $typeStats->toArray(),
                'daily_stats' => $dailyStats->toArray(),
                'survey_scores' => $surveyScores,
            ];

        } catch (\Exception $e) {
            Log::error('フィードバック統計取得エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return [
                'total_feedback' => 0,
                'unique_feedbackers' => 0,
                'documents_with_feedback' => 0,
                'type_stats' => [],
                'daily_stats' => [],
                'survey_scores' => [],
            ];
        }
    }

    /**
     * ドキュメント別のフィードバック統計を取得
     */
    public function getDocumentFeedbackStatistics(string $documentId): array
    {
        try {
            $stats = DocumentFeedback::select([
                    \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_feedback'),
                    \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT feedbacker_ip) as unique_feedbackers')
                ])
                ->where('document_id', $documentId)
                ->first();

            // フィードバックタイプ別統計
            $typeStats = DocumentFeedback::select([
                    'feedback_type',
                    \Illuminate\Support\Facades\DB::raw('COUNT(*) as count')
                ])
                ->where('document_id', $documentId)
                ->groupBy('feedback_type')
                ->get();

            return [
                'total_feedback' => $stats->total_feedback ?? 0,
                'unique_feedbackers' => $stats->unique_feedbackers ?? 0,
                'type_stats' => $typeStats->toArray(),
            ];

        } catch (\Exception $e) {
            Log::error('ドキュメントフィードバック統計取得エラー: ' . $e->getMessage(), ['document_id' => $documentId]);
            return [
                'total_feedback' => 0,
                'unique_feedbackers' => 0,
                'type_stats' => [],
            ];
        }
    }

    /**
     * 最近のフィードバックを取得
     */
    public function getRecentFeedback(string $companyId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return DocumentFeedback::select('document_feedback.*', 'documents.title', 'documents.file_name')
                ->join('documents', 'document_feedback.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->orderBy('document_feedback.created_at', 'desc')
                ->limit($limit)
                ->get();

        } catch (\Exception $e) {
            Log::error('最近のフィードバック取得エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return collect([]);
        }
    }

    /**
     * アンケートスコアを計算
     */
    public function calculateSurveyScores(string $companyId): array
    {
        try {
            $feedbacks = DocumentFeedback::select('feedback_metadata')
                ->join('documents', 'document_feedback.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->whereNotNull('feedback_metadata')
                ->get();

            $allScores = [];
            $scoresByQuestion = [];

            foreach ($feedbacks as $feedback) {
                $metadata = is_string($feedback->feedback_metadata)
                    ? json_decode($feedback->feedback_metadata, true)
                    : $feedback->feedback_metadata;

                if (is_array($metadata) && isset($metadata['survey_results'])) {
                    foreach ($metadata['survey_results'] as $result) {
                        if (isset($result['value']) && is_numeric($result['value'])) {
                            $score = (float)$result['value'];
                            $question = $result['question'] ?? 'unknown';

                            $allScores[] = $score;

                            if (!isset($scoresByQuestion[$question])) {
                                $scoresByQuestion[$question] = [];
                            }
                            $scoresByQuestion[$question][] = $score;
                        }
                    }
                }
            }

            // 全体の統計
            $overallStats = [];
            if (!empty($allScores)) {
                $overallStats = [
                    'average' => round(array_sum($allScores) / count($allScores), 2),
                    'min' => min($allScores),
                    'max' => max($allScores),
                    'count' => count($allScores),
                ];
            }

            // 質問別の統計
            $questionStats = [];
            foreach ($scoresByQuestion as $question => $scores) {
                $questionStats[$question] = [
                    'average' => round(array_sum($scores) / count($scores), 2),
                    'min' => min($scores),
                    'max' => max($scores),
                    'count' => count($scores),
                ];
            }

            return [
                'overall' => $overallStats,
                'by_question' => $questionStats,
            ];

        } catch (\Exception $e) {
            Log::error('アンケートスコア計算エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return [
                'overall' => [],
                'by_question' => [],
            ];
        }
    }

    /**
     * 特定タイプのフィードバックを取得
     */
    public function getFeedbackByType(string $companyId, string $feedbackType, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator
    {
        try {
            return DocumentFeedback::select('document_feedback.*', 'documents.title', 'documents.file_name')
                ->join('documents', 'document_feedback.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->where('document_feedback.feedback_type', $feedbackType)
                ->orderBy('document_feedback.created_at', 'desc')
                ->paginate($perPage);

        } catch (\Exception $e) {
            Log::error('タイプ別フィードバック取得エラー: ' . $e->getMessage(), [
                'company_id' => $companyId,
                'feedback_type' => $feedbackType
            ]);
            throw $e;
        }
    }

    /**
     * フィードバック送信者の統計を取得
     */
    public function getFeedbackerStatistics(string $companyId): array
    {
        try {
            $feedbackerStats = DocumentFeedback::select([
                    'feedbacker_ip',
                    \Illuminate\Support\Facades\DB::raw('COUNT(*) as feedback_count'),
                    \Illuminate\Support\Facades\DB::raw('COUNT(DISTINCT document_id) as documents_reviewed'),
                    \Illuminate\Support\Facades\DB::raw('MAX(document_feedback.created_at) as last_feedback')
                ])
                ->join('documents', 'document_feedback.document_id', '=', 'documents.id')
                ->where('documents.company_id', $companyId)
                ->groupBy('feedbacker_ip')
                ->orderBy('feedback_count', 'desc')
                ->limit(20)
                ->get();

            return $feedbackerStats->toArray();

        } catch (\Exception $e) {
            Log::error('フィードバック送信者統計取得エラー: ' . $e->getMessage(), ['company_id' => $companyId]);
            return [];
        }
    }
}
