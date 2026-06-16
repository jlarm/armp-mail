<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SendFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'sendable_type',
    'sendable_id',
    'subscriber_id',
    'transport_message_id',
    'sent_at',
    'failed_at',
    'failure_reason',
    'opened_at',
    'clicked_at',
    'bounced_at',
    'complained_at',
    'unsubscribed_at',
])]
class Send extends Model
{
    /** @use HasFactory<SendFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'uuid' => 'string',
            'sendable_type' => 'string',
            'sendable_id' => 'integer',
            'subscriber_id' => 'integer',
            'transport_message_id' => 'string',
            'failure_reason' => 'string',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
            'bounced_at' => 'datetime',
            'complained_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function sendable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Subscriber, $this>
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }
}
