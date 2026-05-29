<?php

namespace Tests\Feature;

use App\Models\AdminNotificationSetting;
use App\Models\CourseApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourseApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_course_application_is_stored_and_sent_to_telegram(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        AdminNotificationSetting::create([
            'telegram_bot_token' => 'test-token',
            'telegram_chat_id' => '12345',
        ]);

        $response = $this->postJson('/api/v1/public/applications', [
            'full_name' => 'Ivan Ivanov',
            'phone' => '+77001234567',
            'course' => 'Python Starter',
            'message' => 'Trial lesson request',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('type', CourseApplication::TYPE_COURSE)
            ->assertJsonStructure(['application_id']);

        $this->assertDatabaseHas('course_applications', [
            'type' => CourseApplication::TYPE_COURSE,
            'full_name' => 'Ivan Ivanov',
            'phone' => '+77001234567',
            'course' => 'Python Starter',
            'message' => 'Trial lesson request',
        ]);

        Http::assertSent(fn ($request) =>
            $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
            && $request['chat_id'] === '12345'
            && str_contains($request['text'], 'Ivan Ivanov')
            && str_contains($request['text'], 'Python Starter')
        );
    }

    public function test_public_feedback_application_is_stored_and_sent_to_telegram(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        AdminNotificationSetting::create([
            'telegram_bot_token' => 'test-token',
            'telegram_chat_id' => '12345',
        ]);

        $response = $this->postJson('/api/v1/public/feedback', [
            'full_name' => 'Alex Petrov',
            'phone' => '+77007654321',
            'questions' => 'Question about schedule',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('type', CourseApplication::TYPE_FEEDBACK)
            ->assertJsonStructure(['application_id']);

        $this->assertDatabaseHas('course_applications', [
            'type' => CourseApplication::TYPE_FEEDBACK,
            'full_name' => 'Alex Petrov',
            'phone' => '+77007654321',
            'course' => null,
            'message' => 'Question about schedule',
        ]);

        Http::assertSent(fn ($request) =>
            $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
            && $request['chat_id'] === '12345'
            && str_contains($request['text'], 'Alex Petrov')
            && str_contains($request['text'], 'Question about schedule')
        );
    }

    public function test_admin_can_update_notification_settings(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $response = $this->putJson('/api/v1/admin/notification-settings', [
            'email' => 'admin@example.com',
            'telegram_bot_token' => 'new-token',
            'telegram_chat_id' => '-100123456',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'admin@example.com')
            ->assertJsonPath('data.telegram_bot_token', 'new-token')
            ->assertJsonPath('data.telegram_chat_id', '-100123456');

        $this->assertDatabaseHas('admin_notification_settings', [
            'email' => 'admin@example.com',
            'telegram_bot_token' => 'new-token',
            'telegram_chat_id' => '-100123456',
        ]);
    }
    public function test_admin_can_set_telegram_recipient_alias(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $response = $this->putJson('/api/v1/admin/notification-settings', [
            'telegram_bot_token' => 'new-token',
            'telegram_recipient' => '@codzilla_requests',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.telegram_bot_token', 'new-token')
            ->assertJsonPath('data.telegram_chat_id', '@codzilla_requests');

        $this->assertDatabaseHas('admin_notification_settings', [
            'telegram_bot_token' => 'new-token',
            'telegram_chat_id' => '@codzilla_requests',
        ]);
    }

    public function test_admin_can_resolve_telegram_chat_id_from_bot_updates(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        Http::fake([
            'api.telegram.org/botnew-token/getUpdates' => Http::response([
                'ok' => true,
                'result' => [
                    [
                        'message' => [
                            'chat' => [
                                'id' => 987654321,
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->putJson('/api/v1/admin/notification-settings', [
            'telegram_bot_token' => 'new-token',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.telegram_bot_token', 'new-token')
            ->assertJsonPath('data.telegram_chat_id', '987654321');

        $this->assertDatabaseHas('admin_notification_settings', [
            'telegram_bot_token' => 'new-token',
            'telegram_chat_id' => '987654321',
        ]);
    }
}
