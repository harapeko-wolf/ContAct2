<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompanyTimeRexTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->company = Company::factory()->create(['user_id' => $user->id]);
    }

    /** @test */
    public function it_can_add_new_confirmed_booking()
    {
        $bookingData = [
            'event_id' => 'test-event-1',
            'status' => 'confirmed',
            'start_datetime' => '2025-06-06T08:00:00+00:00',
            'guest_name' => 'Test User',
            'created_at' => now()->toISOString(),
        ];

        $this->company->addTimeRexBooking($bookingData);

        $this->company->refresh();
        $bookings = $this->company->timerex_bookings;

        $this->assertEquals(1, $bookings['total_bookings']);
        $this->assertEquals(0, $bookings['total_cancellations']);
        $this->assertCount(1, $bookings['bookings']);
        $this->assertEquals('confirmed', $bookings['bookings'][0]['status']);
    }

    /** @test */
    public function it_can_add_new_cancelled_booking()
    {
        $bookingData = [
            'event_id' => 'test-event-1',
            'status' => 'cancelled',
            'start_datetime' => '2025-06-06T08:00:00+00:00',
            'guest_name' => 'Test User',
            'created_at' => now()->toISOString(),
        ];

        $this->company->addTimeRexBooking($bookingData);

        $this->company->refresh();
        $bookings = $this->company->timerex_bookings;

        $this->assertEquals(0, $bookings['total_bookings']);
        $this->assertEquals(1, $bookings['total_cancellations']);
        $this->assertCount(1, $bookings['bookings']);
        $this->assertEquals('cancelled', $bookings['bookings'][0]['status']);
    }

    /** @test */
    public function it_can_update_confirmed_booking_to_cancelled()
    {
        // 最初に確定予約を追加
        $bookingData = [
            'event_id' => 'test-event-1',
            'status' => 'confirmed',
            'start_datetime' => '2025-06-06T08:00:00+00:00',
            'guest_name' => 'Test User',
            'created_at' => now()->toISOString(),
        ];

        $this->company->addTimeRexBooking($bookingData);

        // 同じ予約をキャンセルに変更
        $cancelData = array_merge($bookingData, [
            'status' => 'cancelled',
            'canceled_at' => now()->toISOString(),
        ]);

        $this->company->addTimeRexBooking($cancelData);

        $this->company->refresh();
        $bookings = $this->company->timerex_bookings;

        // confirmed → cancelled で total_bookings は減算、total_cancellations は加算
        $this->assertEquals(0, $bookings['total_bookings']);
        $this->assertEquals(1, $bookings['total_cancellations']);
        $this->assertCount(1, $bookings['bookings']);
        $this->assertEquals('cancelled', $bookings['bookings'][0]['status']);
    }

    /** @test */
    public function it_can_update_cancelled_booking_to_confirmed()
    {
        // 最初にキャンセル予約を追加
        $bookingData = [
            'event_id' => 'test-event-1',
            'status' => 'cancelled',
            'start_datetime' => '2025-06-06T08:00:00+00:00',
            'guest_name' => 'Test User',
            'created_at' => now()->toISOString(),
        ];

        $this->company->addTimeRexBooking($bookingData);

        // 同じ予約を確定に変更（再予約）
        $confirmData = array_merge($bookingData, [
            'status' => 'confirmed',
        ]);

        $this->company->addTimeRexBooking($confirmData);

        $this->company->refresh();
        $bookings = $this->company->timerex_bookings;

        // cancelled → confirmed で total_bookings は加算、total_cancellations は減算
        $this->assertEquals(1, $bookings['total_bookings']);
        $this->assertEquals(0, $bookings['total_cancellations']);
        $this->assertCount(1, $bookings['bookings']);
        $this->assertEquals('confirmed', $bookings['bookings'][0]['status']);
    }

    /** @test */
    public function it_does_not_duplicate_count_on_same_status_update()
    {
        // 最初に確定予約を追加
        $bookingData = [
            'event_id' => 'test-event-1',
            'status' => 'confirmed',
            'start_datetime' => '2025-06-06T08:00:00+00:00',
            'guest_name' => 'Test User',
            'created_at' => now()->toISOString(),
        ];

        $this->company->addTimeRexBooking($bookingData);

        // 同じステータスで再度更新（重複処理シミュレーション）
        $duplicateData = array_merge($bookingData, [
            'updated_at' => now()->toISOString(),
        ]);

        $this->company->addTimeRexBooking($duplicateData);

        $this->company->refresh();
        $bookings = $this->company->timerex_bookings;

        // カウンターは変わらない
        $this->assertEquals(1, $bookings['total_bookings']);
        $this->assertEquals(0, $bookings['total_cancellations']);
        $this->assertCount(1, $bookings['bookings']);
    }

    /** @test */
    public function it_handles_multiple_different_bookings()
    {
        // 3つの異なる予約を追加
        $bookings = [
            [
                'event_id' => 'test-event-1',
                'status' => 'confirmed',
                'start_datetime' => '2025-06-06T08:00:00+00:00',
                'guest_name' => 'User 1',
                'created_at' => now()->toISOString(),
            ],
            [
                'event_id' => 'test-event-2',
                'status' => 'confirmed',
                'start_datetime' => '2025-06-06T09:00:00+00:00',
                'guest_name' => 'User 2',
                'created_at' => now()->toISOString(),
            ],
            [
                'event_id' => 'test-event-3',
                'status' => 'cancelled',
                'start_datetime' => '2025-06-06T10:00:00+00:00',
                'guest_name' => 'User 3',
                'created_at' => now()->toISOString(),
            ],
        ];

        foreach ($bookings as $booking) {
            $this->company->addTimeRexBooking($booking);
        }

        $this->company->refresh();
        $companyBookings = $this->company->timerex_bookings;

        $this->assertEquals(2, $companyBookings['total_bookings']);
        $this->assertEquals(1, $companyBookings['total_cancellations']);
        $this->assertCount(3, $companyBookings['bookings']);
    }

    /** @test */
    public function it_handles_complex_status_changes()
    {
        $eventId = 'test-event-1';

        // 1. 確定予約を追加
        $this->company->addTimeRexBooking([
            'event_id' => $eventId,
            'status' => 'confirmed',
            'start_datetime' => '2025-06-06T08:00:00+00:00',
            'guest_name' => 'Test User',
            'created_at' => now()->toISOString(),
        ]);

        // 2. キャンセルに変更
        $this->company->addTimeRexBooking([
            'event_id' => $eventId,
            'status' => 'cancelled',
            'start_datetime' => '2025-06-06T08:00:00+00:00',
            'guest_name' => 'Test User',
            'canceled_at' => now()->toISOString(),
        ]);

        // 3. 再度確定に変更
        $this->company->addTimeRexBooking([
            'event_id' => $eventId,
            'status' => 'confirmed',
            'start_datetime' => '2025-06-06T08:00:00+00:00',
            'guest_name' => 'Test User',
            'rescheduled_at' => now()->toISOString(),
        ]);

        $this->company->refresh();
        $bookings = $this->company->timerex_bookings;

        // 最終的に確定予約として集計される
        $this->assertEquals(1, $bookings['total_bookings']);
        $this->assertEquals(0, $bookings['total_cancellations']);
        $this->assertCount(1, $bookings['bookings']);
        $this->assertEquals('confirmed', $bookings['bookings'][0]['status']);
    }

    /** @test */
    public function it_initializes_empty_bookings_correctly()
    {
        $bookingData = [
            'event_id' => 'test-event-1',
            'status' => 'confirmed',
            'start_datetime' => '2025-06-06T08:00:00+00:00',
            'guest_name' => 'Test User',
            'created_at' => now()->toISOString(),
        ];

        // 初期状態でtimerex_bookingsがnullの場合の処理
        $this->assertNull($this->company->timerex_bookings);

        $this->company->addTimeRexBooking($bookingData);

        $this->company->refresh();
        $bookings = $this->company->timerex_bookings;

        $this->assertIsArray($bookings);
        $this->assertArrayHasKey('total_bookings', $bookings);
        $this->assertArrayHasKey('total_cancellations', $bookings);
        $this->assertArrayHasKey('bookings', $bookings);
    }

    /** @test */
    public function it_provides_correct_timerex_stats_attribute()
    {
        // 複数の予約を追加
        $bookings = [
            [
                'event_id' => 'event-1',
                'status' => 'confirmed',
                'created_at' => '2025-06-05T08:00:00+00:00',
            ],
            [
                'event_id' => 'event-2',
                'status' => 'confirmed',
                'created_at' => '2025-06-04T08:00:00+00:00',
            ],
            [
                'event_id' => 'event-3',
                'status' => 'cancelled',
                'created_at' => '2025-06-03T08:00:00+00:00',
            ],
        ];

        foreach ($bookings as $booking) {
            $this->company->addTimeRexBooking($booking);
        }

        $stats = $this->company->timeRex_stats;

        $this->assertEquals(2, $stats['total_bookings']);
        $this->assertEquals(1, $stats['total_cancellations']);
        $this->assertCount(3, $stats['recent_bookings']);
        // 最新順でソートされているか確認
        $this->assertEquals('event-1', $stats['recent_bookings'][0]['event_id']);
    }

    /** @test */
    public function it_provides_correct_latest_booking_attribute()
    {
        // 複数の予約を追加
        $this->company->addTimeRexBooking([
            'event_id' => 'event-1',
            'status' => 'confirmed',
            'created_at' => '2025-06-03T08:00:00+00:00',
        ]);

        $this->company->addTimeRexBooking([
            'event_id' => 'event-2',
            'status' => 'cancelled',
            'created_at' => '2025-06-05T08:00:00+00:00',
        ]);

        $latest = $this->company->latest_timeRex_booking;

        $this->assertEquals('event-2', $latest['event_id']);
        $this->assertEquals('cancelled', $latest['status']);
    }
}
