<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AutomationActionSubscriberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'automation_id',
    'automation_step_id',
    'subscriber_id',
    'run_at',
    'completed_at',
    'halted_at',
])]
class AutomationActionSubscriber extends Model
{
    /** @use HasFactory<AutomationActionSubscriberFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'automation_id' => 'integer',
            'automation_step_id' => 'integer',
            'subscriber_id' => 'integer',
            'run_at' => 'datetime',
            'completed_at' => 'datetime',
            'halted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Automation, $this>
     */
    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    /**
     * @return BelongsTo<AutomationStep, $this>
     */
    public function automationStep(): BelongsTo
    {
        return $this->belongsTo(AutomationStep::class);
    }

    /**
     * @return BelongsTo<Subscriber, $this>
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }
}
