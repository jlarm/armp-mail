<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EmailListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'slug',
    'description',
    'default_from_email',
    'default_from_name',
    'default_reply_to_email',
    'requires_confirmation',
    'redirect_after_subscribed',
    'redirect_after_unsubscribed',
    'campaign_mails_per_minute',
])]
class EmailList extends Model
{
    /** @use HasFactory<EmailListFactory> */
    use HasFactory;

    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'name' => 'string',
            'slug' => 'string',
            'description' => 'string',
            'default_from_email' => 'string',
            'default_from_name' => 'string',
            'default_reply_to_email' => 'string',
            'requires_confirmation' => 'boolean',
            'redirect_after_subscribed' => 'string',
            'redirect_after_unsubscribed' => 'string',
            'campaign_mails_per_minute' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Subscriber, $this, EmailListSubscriber, 'pivot'>
     */
    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(Subscriber::class, 'email_list_subscribers')
            ->using(EmailListSubscriber::class)
            ->withPivot(['status', 'subscribed_at', 'unsubscribed_at', 'subscribe_source'])
            ->withTimestamps();
    }
}
