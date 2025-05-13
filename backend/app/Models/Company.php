<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'email',
        'phone',
        'address',
        'website',
        'description',
        'industry',
        'employee_count',
        'status',
    ];

    protected $casts = [
        'employee_count' => 'integer',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
