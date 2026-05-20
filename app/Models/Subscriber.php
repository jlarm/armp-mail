<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SubscriberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['email', 'first_name', 'last_name', 'extra_attributes'])]
class Subscriber extends Model
{
    /** @use HasFactory<SubscriberFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'email' => 'string',
            'first_name' => 'string',
            'last_name' => 'string',
            'extra_attributes' => 'json',
            'uuid' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<EmailList, $this, EmailListSubscriber, 'pivot'>
     */
    public function emailLists(): BelongsToMany
    {
        return $this->belongsToMany(EmailList::class, 'email_list_subscribers')
            ->using(EmailListSubscriber::class)
            ->withPivot(['status', 'subscribed_at', 'unsubscribed_at', 'subscribe_source'])
            ->withTimestamps();
    }
}
