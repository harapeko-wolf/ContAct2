<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FollowupEmail extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'company_id',
        'document_id',
        'viewer_ip',
        'triggered_at',
        'scheduled_for',
        'sent_at',
        'status',
        'cancellation_reason',
        'error_message',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * 会社との関連
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * ドキュメントとの関連
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * 送信予定のフォローアップメールを取得
     */
    public static function getScheduledEmails()
    {
        return self::where('status', 'scheduled')
            ->where('scheduled_for', '<=', now())
            ->with(['company', 'document'])
            ->get();
    }

    /**
     * フォローアップメールをキャンセル
     */
    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * フォローアップメールを送信済みにマーク
     */
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * フォローアップメールを失敗にマーク
     */
    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * 同じ条件の既存フォローアップメールを取得
     */
    public static function findExisting($companyId, $documentId, $viewerIp)
    {
        return self::where('company_id', $companyId)
            ->where('document_id', $documentId)
            ->where('viewer_ip', $viewerIp)
            ->where('status', 'scheduled')
            ->first();
    }
}
