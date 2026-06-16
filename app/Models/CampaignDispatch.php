<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CampaignDispatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'campaign_id',
    'status',
    'scheduled_at',
    'sent_at',
    'sent_to_count',
    'open_count',
    'unique_open_count',
    'click_count',
    'unique_click_count',
    'bounce_count',
    'unsubscribe_count',
])]
class CampaignDispatch extends Model
{
    /** @use HasFactory<CampaignDispatchFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'campaign_id' => 'integer',
            'status' => 'string',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'sent_to_count' => 'integer',
            'open_count' => 'integer',
            'unique_open_count' => 'integer',
            'click_count' => 'integer',
            'unique_click_count' => 'integer',
            'bounce_count' => 'integer',
            'unsubscribe_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Per-recipient sends belong to a dispatch so each occurrence's opens and
     * clicks are tracked independently.
     *
     * @return MorphMany<Send, $this>
     */
    public function sends(): MorphMany
    {
        return $this->morphMany(Send::class, 'sendable');
    }
}
