<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\FollowupEmailService;
use App\Models\Company;
use App\Models\Document;
use App\Models\FollowupEmail;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

class FollowupEmailServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FollowupEmailService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FollowupEmailService::class);

        // メールのモック設定
        Mail::fake();
    }

        public function test_start_followup_timer_success()
    {
        // テストデータ作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $company = Company::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'name' => 'テスト会社',
            'email' => 'test@example.com',
        ]);

        $document = Document::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'company_id' => $company->id,
            'title' => 'テストドキュメント',
            'file_path' => 'test.pdf',
            'file_name' => 'test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        // 設定値を作成
        AppSetting::create([
            'key' => 'email.followup_enabled',
            'value' => true,
            'type' => 'boolean',
        ]);

        AppSetting::create([
            'key' => 'email.followup_delay_minutes',
            'value' => 15,
            'type' => 'number',
        ]);

        // サービス実行
        $result = $this->service->startFollowupTimer(
            $company->id,
            $document->id,
            '192.168.1.1'
        );

        // 結果検証
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(15, $result['data']['delay_minutes']);

        // データベース検証
        $this->assertDatabaseHas('followup_emails', [
            'company_id' => $company->id,
            'document_id' => $document->id,
            'viewer_ip' => '192.168.1.1',
            'status' => 'scheduled',
        ]);
    }

    public function test_start_followup_timer_company_not_found()
    {
        $result = $this->service->startFollowupTimer(
            'non-existent-id',
            'non-existent-id',
            '192.168.1.1'
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('会社が見つかりません', $result['message']);
    }

        public function test_start_followup_timer_no_email()
    {
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $company = Company::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'name' => 'テスト会社',
            'email' => null,
        ]);

        $document = Document::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'company_id' => $company->id,
            'title' => 'テストドキュメント',
            'file_path' => 'test.pdf',
            'file_name' => 'test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $result = $this->service->startFollowupTimer(
            $company->id,
            $document->id,
            '192.168.1.1'
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('会社のメールアドレスが設定されていません', $result['message']);
    }

    public function test_stop_followup_timer_success()
    {
        // テストデータ作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $company = Company::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'name' => 'テスト会社',
            'email' => 'test@example.com',
        ]);

        $document = Document::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'company_id' => $company->id,
            'title' => 'テストドキュメント',
            'file_path' => 'test.pdf',
            'file_name' => 'test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        // 既存のフォローアップメールを作成
        $followup = FollowupEmail::create([
            'company_id' => $company->id,
            'document_id' => $document->id,
            'viewer_ip' => '192.168.1.1',
            'triggered_at' => now(),
            'scheduled_for' => now()->addMinutes(15),
            'status' => 'scheduled',
        ]);

        // サービス実行
        $result = $this->service->stopFollowupTimer(
            $company->id,
            $document->id,
            '192.168.1.1',
            'user_dismissed'
        );

        // 結果検証
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['data']['cancelled_count']);

        // データベース検証
        $followup->refresh();
        $this->assertEquals('cancelled', $followup->status);
        $this->assertEquals('user_dismissed', $followup->cancellation_reason);
    }

    public function test_check_and_cancel_for_timerex_booking_no_bookings()
    {
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $company = Company::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'name' => 'テスト会社',
            'email' => 'test@example.com',
            'timerex_bookings' => null,
        ]);

        $result = $this->service->checkAndCancelForTimeRexBooking($company->id);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['data']['has_recent_booking']);
    }

    public function test_check_and_cancel_for_timerex_booking_with_recent_booking()
    {
        // テストデータ作成
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $company = Company::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'name' => 'テスト会社',
            'email' => 'test@example.com',
            'timerex_bookings' => [
                'total_bookings' => 1,
                'bookings' => [
                    [
                        'event_id' => 'test-event-id',
                        'status' => 'confirmed',
                        'created_at' => now()->subMinutes(10)->toISOString(),
                    ]
                ]
            ]
        ]);

        $document = Document::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'company_id' => $company->id,
            'title' => 'テストドキュメント',
            'file_path' => 'test.pdf',
            'file_name' => 'test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        // フォローアップメールを作成
        $followup = FollowupEmail::create([
            'company_id' => $company->id,
            'document_id' => $document->id,
            'viewer_ip' => '192.168.1.1',
            'triggered_at' => now(),
            'scheduled_for' => now()->addMinutes(15),
            'status' => 'scheduled',
        ]);

        // サービス実行
        $result = $this->service->checkAndCancelForTimeRexBooking($company->id);

        // 結果検証
        $this->assertTrue($result['success']);
        $this->assertTrue($result['data']['has_recent_booking']);
        $this->assertEquals(1, $result['data']['cancelled_count']);

        // データベース検証
        $followup->refresh();
        $this->assertEquals('cancelled', $followup->status);
        $this->assertEquals('timerex_booking_confirmed', $followup->cancellation_reason);
    }
}
