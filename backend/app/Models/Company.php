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
        'timerex_bookings',
    ];

    protected $casts = [
        'employee_count' => 'integer',
        'timerex_bookings' => 'array',
    ];

    protected $attributes = [
        'phone' => null,
        'address' => null,
        'website' => null,
        'description' => null,
        'industry' => null,
        'employee_count' => null,
        'timerex_bookings' => null,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * TimeRex予約統計を取得
     */
    public function getTimeRexStatsAttribute()
    {
        $bookings = $this->timerex_bookings ?? [];

        return [
            'total_bookings' => $bookings['total_bookings'] ?? 0,
            'total_cancellations' => $bookings['total_cancellations'] ?? 0,
            'recent_bookings' => collect($bookings['bookings'] ?? [])
                ->sortByDesc('created_at')
                ->take(5)
                ->values()
                ->all(),
        ];
    }

    /**
     * TimeRex予約を追加
     */
    public function addTimeRexBooking(array $bookingData)
    {
        $currentBookings = $this->timerex_bookings ?? [
            'total_bookings' => 0,
            'total_cancellations' => 0,
            'bookings' => []
        ];

        // 既存予約をチェック（重複防止）
        $existingBooking = collect($currentBookings['bookings'])
            ->firstWhere('event_id', $bookingData['event_id']);

        if ($existingBooking) {
            $previousStatus = $existingBooking['status'] ?? null;
            $newStatus = $bookingData['status'];

            // 既存予約のステータス更新
            $currentBookings['bookings'] = collect($currentBookings['bookings'])
                ->map(function ($booking) use ($bookingData) {
                    if ($booking['event_id'] === $bookingData['event_id']) {
                        return array_merge($booking, $bookingData);
                    }
                    return $booking;
                })
                ->all();

            // ステータス変更に応じてカウンター更新
            if ($previousStatus !== $newStatus) {
                // confirmed → cancelled の場合
                if ($previousStatus === 'confirmed' && $newStatus === 'cancelled') {
                    $currentBookings['total_bookings']--;
                    $currentBookings['total_cancellations']++;
                }
                // cancelled → confirmed の場合（再予約など）
                elseif ($previousStatus === 'cancelled' && $newStatus === 'confirmed') {
                    $currentBookings['total_bookings']++;
                    $currentBookings['total_cancellations']--;
                }
                // 初回ステータスが cancelled の場合（previousStatus が null など）
                elseif ($previousStatus === null && $newStatus === 'cancelled') {
                    $currentBookings['total_cancellations']++;
                }
                // 初回ステータスが confirmed の場合（previousStatus が null など）
                elseif ($previousStatus === null && $newStatus === 'confirmed') {
                    $currentBookings['total_bookings']++;
                }
            }
        } else {
            // 新規予約追加
            $currentBookings['bookings'][] = $bookingData;

            if ($bookingData['status'] === 'confirmed') {
                $currentBookings['total_bookings']++;
            } elseif ($bookingData['status'] === 'cancelled') {
                $currentBookings['total_cancellations']++;
            }
        }

        $this->update(['timerex_bookings' => $currentBookings]);
    }

    /**
     * 最新のTimeRex予約を取得
     */
    public function getLatestTimeRexBookingAttribute()
    {
        $bookings = $this->timerex_bookings['bookings'] ?? [];

        return collect($bookings)
            ->sortByDesc('created_at')
            ->first();
    }
}
