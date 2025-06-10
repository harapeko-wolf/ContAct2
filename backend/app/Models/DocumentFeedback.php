<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentFeedback extends Model
{
    use HasUuids, HasFactory;

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
