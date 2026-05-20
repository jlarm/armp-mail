<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AutomationStepType;
use Database\Factories\AutomationStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'automation_id',
    'type',
    'order',
    'parent_step_id',
    'config',
])]
class AutomationStep extends Model
{
    /** @use HasFactory<AutomationStepFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'automation_id' => 'integer',
            'type' => AutomationStepType::class,
            'order' => 'integer',
            'parent_step_id' => 'integer',
            'config' => 'json',
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
    public function parent(): BelongsTo
    {
        return $this->belongsTo(AutomationStep::class, 'parent_step_id');
    }

    /**
     * @return HasMany<AutomationStep, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(AutomationStep::class, 'parent_step_id');
    }

    /**
     * @return HasMany<AutomationActionSubscriber, $this>
     */
    public function actionSubscribers(): HasMany
    {
        return $this->hasMany(AutomationActionSubscriber::class);
    }
}
