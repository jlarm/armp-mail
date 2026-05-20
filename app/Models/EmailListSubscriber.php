<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Override;

#[Fillable(['status', 'subscribed_at', 'unsubscribed_at', 'subscribe_source'])]
#[Table(incrementing: true)]
class EmailListSubscriber extends Pivot
{
    #[Override]
    protected $table = 'email_list_subscribers';

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'email_list_id' => 'integer',
            'subscriber_id' => 'integer',
            'status' => Status::class,
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
