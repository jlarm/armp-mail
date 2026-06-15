<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignFrequency;
use App\Enums\CampaignStatus;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'email_list_id',
    'segment_id',
    'template_id',
    'name',
    'subject',
    'from_email',
    'from_name',
    'reply_to_email',
    'html',
    'content_json',
    'structured_html',
    'status',
    'frequency',
    'track_opens',
    'track_clicks',
    'scheduled_at',
    'next_run_at',
    'sent_at',
    'last_sent_at',
])]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'email_list_id' => 'integer',
            'segment_id' => 'integer',
            'template_id' => 'integer',
            'name' => 'string',
            'subject' => 'string',
            'from_email' => 'string',
            'from_name' => 'string',
            'reply_to_email' => 'string',
            'html' => 'string',
            'content_json' => 'json',
            'structured_html' => 'string',
            'status' => CampaignStatus::class,
            'frequency' => CampaignFrequency::class,
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean',
            'sent_to_count' => 'integer',
            'open_count' => 'integer',
            'unique_open_count' => 'integer',
            'click_count' => 'integer',
            'unique_click_count' => 'integer',
            'bounce_count' => 'integer',
            'unsubscribe_count' => 'integer',
            'scheduled_at' => 'datetime',
            'next_run_at' => 'datetime',
            'sent_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<CampaignDispatch, $this>
     */
    public function dispatches(): HasMany
    {
        return $this->hasMany(CampaignDispatch::class);
    }

    /**
     * @return BelongsTo<EmailList, $this>
     */
    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    /**
     * @return BelongsTo<Segment, $this>
     */
    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    /**
     * @return BelongsTo<Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
