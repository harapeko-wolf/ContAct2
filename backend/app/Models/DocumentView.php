<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentView extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'document_id',
        'viewer_ip',
        'viewer_user_agent',
        'page_number',
        'view_duration',
        'viewed_at',
        'viewer_metadata',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'viewer_metadata' => 'array',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
