<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'company_id',
        'title',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'status',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function views()
    {
        return $this->hasMany(DocumentView::class);
    }

    public function feedback()
    {
        return $this->hasMany(DocumentFeedback::class);
    }
}
