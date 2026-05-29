<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\AdminNotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class NotificationSettingController
{
    public function show()
    {
        return response()->json([
            'success' => true,
            'data' => AdminNotificationSetting::current(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'email' => 'nullable|email|max:255',
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_id' => 'nullable|string|max:255',
            'telegram_recipient' => 'nullable|string|max:255',
            'resolve_telegram_chat' => 'nullable|boolean',
        ]);

        $settings = AdminNotificationSetting::current();

        if (!empty($validated['telegram_recipient'])) {
            $validated['telegram_chat_id'] = trim($validated['telegram_recipient']);
        }

        $shouldResolveChat = !empty($validated['resolve_telegram_chat'])
            || ($request->has('telegram_bot_token')
                && !$request->filled('telegram_chat_id')
                && !$request->filled('telegram_recipient'));

        if ($shouldResolveChat) {
            $botToken = $validated['telegram_bot_token'] ?? $settings->telegram_bot_token;

            if (!$botToken) {
                throw ValidationException::withMessages([
                    'telegram_bot_token' => ['Укажите Telegram bot token для автоматического поиска chat_id.'],
                ]);
            }

            $validated['telegram_chat_id'] = $this->resolveTelegramChatId($botToken);
        }

        unset($validated['telegram_recipient'], $validated['resolve_telegram_chat']);

        $settings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Настройки уведомлений обновлены',
            'data' => $settings->fresh(),
        ]);
    }

    private function resolveTelegramChatId(string $botToken): string
    {
        $response = Http::timeout(10)->get("https://api.telegram.org/bot{$botToken}/getUpdates");

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'telegram_bot_token' => ['Telegram не принял bot token или временно недоступен.'],
            ]);
        }

        $updates = $response->json('result', []);

        foreach (array_reverse($updates) as $update) {
            $chat = $update['message']['chat']
                ?? $update['edited_message']['chat']
                ?? $update['channel_post']['chat']
                ?? null;

            if (!empty($chat['id'])) {
                return (string) $chat['id'];
            }
        }

        throw ValidationException::withMessages([
            'telegram_chat_id' => ['Не удалось найти chat_id. Напишите боту /start и повторите сохранение.'],
        ]);
    }
}