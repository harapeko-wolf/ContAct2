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
        'company_id',
        'title',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'page_count',
        'status',
        'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'page_count' => 'integer',
        'metadata' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
