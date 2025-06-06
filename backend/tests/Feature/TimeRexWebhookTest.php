<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Str;

class TimeRexWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用の会社を作成（有効なUUID形式で）
        $user = User::factory()->create();
        $this->company = Company::factory()->create([
            'id' => '01234567-89ab-cdef-0123-456789abcdef',
            'user_id' => $user->id,
        ]);
    }

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * 予約確定Webhookのテスト
     */
    public function test_event_confirmed_webhook()
    {
        $payload = $this->getEventConfirmedPayload();

        $response = $this->postJson('/api/timerex/webhook', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'company_id' => '01234567-89ab-cdef-0123-456789abcdef',
                    'event_id' => 'd7ecbe650326308aad6b',
                    'status' => 'confirmed'
                ]
            ]);

        // データベースに保存されているか確認
        $this->company->refresh();
        $bookings = $this->company->timerex_bookings;

        $this->assertNotNull($bookings);
        $this->assertEquals(1, $bookings['total_bookings']);
        $this->assertEquals(0, $bookings['total_cancellations']);
        $this->assertCount(1, $bookings['bookings']);

        $booking = $bookings['bookings'][0];
        $this->assertEquals('d7ecbe650326308aad6b', $booking['event_id']);
        $this->assertEquals('confirmed', $booking['status']);
        $this->assertEquals('harapeko.inc', $booking['guest_name']);
        $this->assertEquals('harapeko.inc@gmail.com', $booking['guest_email']);
    }

    /**
     * 予約キャンセルWebhookのテスト
     */
    public function test_event_cancelled_webhook()
    {
        // 先に予約確定を作成
        $this->company->addTimeRexBooking([
            'event_id' => 'd7ecbe650326308aad6b',
            'status' => 'confirmed',
            'start_datetime' => '2025-06-06T08:00:00+00:00',
            'end_datetime' => '2025-06-06T09:00:00+00:00',
            'guest_name' => 'harapeko.inc',
            'guest_email' => 'harapeko.inc@gmail.com',
            'created_at' => '2025-06-05T13:06:31+00:00',
        ]);

        $payload = $this->getEventCancelledPayload();

        $response = $this->postJson('/api/timerex/webhook', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'company_id' => '01234567-89ab-cdef-0123-456789abcdef',
                    'event_id' => 'd7ecbe650326308aad6b',
                    'status' => 'cancelled'
                ]
            ]);

        // データベースが更新されているか確認
        $this->company->refresh();
        $bookings = $this->company->timerex_bookings;

        $this->assertEquals(0, $bookings['total_bookings']); // confirmed → cancelled で total_bookings は減算される
        $this->assertEquals(1, $bookings['total_cancellations']);

        $booking = $bookings['bookings'][0];
        $this->assertEquals('cancelled', $booking['status']);
        $this->assertNotNull($booking['canceled_at']);
    }

    /**
     * guest_commentから会社IDを取得するテスト
     */
    public function test_webhook_with_company_id_in_guest_comment()
    {
        $payload = $this->getEventConfirmedPayload();
        // URLパラメータを削除してguest_commentに会社IDを設定
        $payload['calendar_url'] = 'https://timerex.net/s/harapeko.inc_7618/1de15a5b';
        $payload['event']['form'][3]['value'] = '01234567-89ab-cdef-0123-456789abcdef';

        $response = $this->postJson('/api/timerex/webhook', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'company_id' => '01234567-89ab-cdef-0123-456789abcdef'
                ]
            ]);
    }

    /**
     * 無効なペイロードのテスト
     */
    public function test_invalid_payload()
    {
        $payload = ['invalid' => 'data'];

        $response = $this->postJson('/api/timerex/webhook', $payload);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error'
            ]);
    }

    /**
     * 存在しない会社IDのテスト
     */
    public function test_nonexistent_company_id()
    {
        $payload = $this->getEventConfirmedPayload();
        $payload['calendar_url'] = 'https://timerex.net/s/harapeko.inc_7618/1de15a5b?company_id=nonexistent-uuid';

        $response = $this->postJson('/api/timerex/webhook', $payload);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error'
            ]);
    }

    /**
     * ヘルスチェックのテスト
     */
    public function test_webhook_health_check()
    {
        $response = $this->getJson('/api/timerex/webhook/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'message' => 'TimeRex Webhook endpoint is healthy'
            ]);
    }

    /**
     * 予約確定ペイロードを取得
     */
    private function getEventConfirmedPayload(): array
    {
        return [
            'webhook_type' => 'event_confirmed',
            'calendar_url_path' => '1de15a5b',
            'team_url_path' => 'harapeko.inc_7618',
            'calendar_url' => 'https://timerex.net/s/harapeko.inc_7618/1de15a5b?company_id=01234567-89ab-cdef-0123-456789abcdef',
            'calendar_name' => 'Test',
            'event' => [
                'id' => 'd7ecbe650326308aad6b',
                'status' => 1,
                'duration' => 60,
                'start_datetime' => '2025-06-06T08:00:00+00:00',
                'end_datetime' => '2025-06-06T09:00:00+00:00',
                'local_start_datetime' => '2025-06-06T17:00:00+09:00',
                'local_end_datetime' => '2025-06-06T18:00:00+09:00',
                'calendar_timezone' => 'Asia/Tokyo',
                'guest_locale' => 'ja',
                'guest_timezone' => 'Asia/Tokyo',
                'created_at' => '2025-06-05T13:06:31+00:00',
                'form' => [
                    [
                        'field_type' => 'company_name',
                        'required' => false,
                        'label' => '会社名',
                        'value' => 'YOLO'
                    ],
                    [
                        'field_type' => 'guest_name',
                        'required' => true,
                        'label' => '名前',
                        'value' => 'harapeko.inc'
                    ],
                    [
                        'field_type' => 'guest_email',
                        'required' => true,
                        'label' => 'メールアドレス',
                        'value' => 'harapeko.inc@gmail.com'
                    ],
                    [
                        'field_type' => 'guest_comment',
                        'required' => false,
                        'label' => 'コメント',
                        'value' => ''
                    ]
                ]
            ]
        ];
    }

    /**
     * 予約キャンセルペイロードを取得
     */
    private function getEventCancelledPayload(): array
    {
        return [
            'webhook_type' => 'event_cancelled',
            'calendar_url_path' => '1de15a5b',
            'team_url_path' => 'harapeko.inc_7618',
            'calendar_url' => 'https://timerex.net/s/harapeko.inc_7618/1de15a5b?company_id=01234567-89ab-cdef-0123-456789abcdef',
            'calendar_name' => 'Test',
            'event' => [
                'id' => 'd7ecbe650326308aad6b',
                'status' => 2,
                'duration' => 60,
                'start_datetime' => '2025-06-06T08:00:00+00:00',
                'end_datetime' => '2025-06-06T09:00:00+00:00',
                'local_start_datetime' => '2025-06-06T17:00:00+09:00',
                'local_end_datetime' => '2025-06-06T18:00:00+09:00',
                'calendar_timezone' => 'Asia/Tokyo',
                'canceled_at' => '2025-06-05T13:12:01+00:00',
                'cancellation_reason' => '',
                'form' => [
                    [
                        'field_type' => 'company_name',
                        'required' => false,
                        'label' => '会社名',
                        'value' => 'YOLO'
                    ],
                    [
                        'field_type' => 'guest_name',
                        'required' => true,
                        'label' => '名前',
                        'value' => 'harapeko.inc'
                    ],
                    [
                        'field_type' => 'guest_email',
                        'required' => true,
                        'label' => 'メールアドレス',
                        'value' => 'harapeko.inc@gmail.com'
                    ],
                    [
                        'field_type' => 'guest_comment',
                        'required' => false,
                        'label' => 'コメント',
                        'value' => ''
                    ]
                ]
            ]
        ];
    }
}
