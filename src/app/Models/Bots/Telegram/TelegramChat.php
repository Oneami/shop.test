<?php

namespace App\Models\Bots\Telegram;

use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $chat_id
 * @property string|null $name
 * @property int $telegram_bot_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Bots\Telegram\TelegramBot|null $bot
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class TelegramChat extends TelegraphChat
{
    use Notifiable;

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'offline_notifications_pause_until' => 'datetime',
    ];

    /**
     * Get the bot that owns the chat.
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }
}
