<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SuppressionReason;
use Database\Factories\SuppressionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'email',
    'reason',
    'notes',
    'suppressed_at',
])]
class Suppression extends Model
{
    /** @use HasFactory<SuppressionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'email' => 'string',
            'reason' => SuppressionReason::class,
            'notes' => 'string',
            'suppressed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
