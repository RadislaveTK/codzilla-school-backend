<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotificationSetting extends Model
{
    protected $fillable = [
        'email',
        'telegram_bot_token',
        'telegram_chat_id',
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate(
            ['id' => 1],
            [
                'email' => env('ADMIN_NOTIFICATION_EMAIL'),
                'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN'),
                'telegram_chat_id' => env('TELEGRAM_CHAT_ID'),
            ]
        );
    }
}
