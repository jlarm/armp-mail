<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SendFeedbackType;
use Database\Factories\SendFeedbackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'send_id',
    'type',
    'url',
    'user_agent',
    'ip_address',
    'payload',
    'happened_at',
])]
class SendFeedback extends Model
{
    /** @use HasFactory<SendFeedbackFactory> */
    use HasFactory;

    #[\Override]
    protected $table = 'send_feedback';

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'send_id' => 'integer',
            'type' => SendFeedbackType::class,
            'url' => 'string',
            'user_agent' => 'string',
            'ip_address' => 'string',
            'payload' => 'json',
            'happened_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Send, $this>
     */
    public function send(): BelongsTo
    {
        return $this->belongsTo(Send::class);
    }
}
