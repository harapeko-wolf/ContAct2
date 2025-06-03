<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DocumentFeedback extends Model
{
    use HasUuids;

    protected $table = 'document_feedback';

    protected $fillable = [
        'document_id',
        'feedback_type',
        'content',
        'feedbacker_ip',
        'feedbacker_user_agent',
        'feedback_metadata',
    ];

    protected $casts = [
        'feedback_metadata' => 'array',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
