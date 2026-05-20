<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AutomationStatus;
use Database\Factories\AutomationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'email_list_id',
    'name',
    'status',
    'triggers',
    'last_ran_at',
])]
class Automation extends Model
{
    /** @use HasFactory<AutomationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'email_list_id' => 'integer',
            'name' => 'string',
            'status' => AutomationStatus::class,
            'triggers' => 'json',
            'last_ran_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EmailList, $this>
     */
    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    /**
     * @return HasMany<AutomationStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(AutomationStep::class);
    }

    /**
     * @return HasMany<AutomationActionSubscriber, $this>
     */
    public function actionSubscribers(): HasMany
    {
        return $this->hasMany(AutomationActionSubscriber::class);
    }
}
