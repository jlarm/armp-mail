<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TransactionalMailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'subject',
    'html',
    'structured_html',
    'store_mail',
    'track_opens',
    'track_clicks',
])]
class TransactionalMail extends Model
{
    /** @use HasFactory<TransactionalMailFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'name' => 'string',
            'subject' => 'string',
            'html' => 'string',
            'structured_html' => 'string',
            'store_mail' => 'boolean',
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
