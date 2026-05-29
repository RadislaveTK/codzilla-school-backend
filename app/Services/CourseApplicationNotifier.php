<?php

namespace App\Services;

use App\Models\AdminNotificationSetting;
use App\Models\CourseApplication;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CourseApplicationNotifier
{
    public function notify(CourseApplication $application): void
    {
        $settings = AdminNotificationSetting::current();
        $emailText = $this->buildPlainMessage($application);
        $telegramText = $this->buildTelegramMessage($application);

        $sent = false;

        if ($settings->email) {
            $sent = $this->sendEmail($settings->email, $emailText, $this->subject($application)) || $sent;
        }

        if ($settings->telegram_bot_token && $settings->telegram_chat_id) {
            $sent = $this->sendTelegram(
                $settings->telegram_bot_token,
                $settings->telegram_chat_id,
                $telegramText
            ) || $sent;
        }

        if ($sent) {
            $application->forceFill(['notified_at' => now()])->save();
        }
    }

    private function buildPlainMessage(CourseApplication $application): string
    {
        $message = $application->message ?: 'Не указано';
        $lines = [
            $this->subject($application),
            str_repeat('=', 32),
            '',
            "Тип заявки: {$this->typeLabel($application)}",
            "Имя и фамилия: {$application->full_name}",
            "Телефон: {$application->phone}",
        ];

        if ($application->type === CourseApplication::TYPE_COURSE) {
            $lines[] = "Курс: {$application->course}";
            $lines[] = "Доп. сообщение: {$message}";
        }

        if ($application->type === CourseApplication::TYPE_FEEDBACK) {
            $lines[] = "Доп. вопросы: {$message}";
        }

        $lines[] = '';
        $lines[] = "Дата заявки: {$application->created_at?->format('d.m.Y H:i')}";

        return implode("\n", $lines);
    }

    private function buildTelegramMessage(CourseApplication $application): string
    {
        $message = $application->message ?: 'Не указано';
        $createdAt = $application->created_at?->format('d.m.Y H:i');

        $lines = [
            '<b>' . $this->escape($this->subject($application)) . '</b>',
            '',
            '<b>Тип:</b> ' . $this->escape($this->typeLabel($application)),
            '<b>Клиент:</b> ' . $this->escape($application->full_name),
            '<b>Телефон:</b> <code>' . $this->escape($application->phone) . '</code>',
        ];

        if ($application->type === CourseApplication::TYPE_COURSE) {
            $lines[] = '<b>Курс:</b> ' . $this->escape((string) $application->course);
            $lines[] = '';
            $lines[] = '<b>Сообщение:</b>';
            $lines[] = $this->escape($message);
        }

        if ($application->type === CourseApplication::TYPE_FEEDBACK) {
            $lines[] = '';
            $lines[] = '<b>Вопросы:</b>';
            $lines[] = $this->escape($message);
        }

        $lines[] = '';
        $lines[] = '<b>Дата:</b> ' . $this->escape((string) $createdAt);

        return implode("\n", $lines);
    }

    private function subject(CourseApplication $application): string
    {
        return match ($application->type) {
            CourseApplication::TYPE_FEEDBACK => 'Новая заявка обратной связи',
            default => 'Новая заявка на курс',
        };
    }

    private function typeLabel(CourseApplication $application): string
    {
        return match ($application->type) {
            CourseApplication::TYPE_FEEDBACK => 'Обратная связь',
            default => 'Запись на курс',
        };
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function sendEmail(string $email, string $text, string $subject): bool
    {
        try {
            Mail::raw($text, function ($message) use ($email, $subject): void {
                $message->to($email)->subject($subject);
            });

            return true;
        } catch (\Throwable $exception) {
            Log::error('Failed to send application email', [
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function sendTelegram(string $botToken, string $chatId, string $text): bool
    {
        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if ($response->failed()) {
                Log::error('Telegram rejected application message', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::error('Failed to send application telegram message', [
                'chat_id' => $chatId,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}