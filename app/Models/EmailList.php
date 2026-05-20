<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EmailListFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailList extends Model
{
    /** @use HasFactory<EmailListFactory> */
    use HasFactory;

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
}
