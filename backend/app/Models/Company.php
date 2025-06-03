<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Company extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'email',
        'phone',
        'address',
        'website',
        'description',
        'industry',
        'employee_count',
        'status',
        'booking_link',
    ];

    protected $casts = [
        'employee_count' => 'integer',
    ];

    protected $attributes = [
        'phone' => null,
        'address' => null,
        'website' => null,
        'description' => null,
        'industry' => null,
        'employee_count' => null,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
