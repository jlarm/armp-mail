<?php

declare(strict_types=1);

namespace App\Enums;

enum AutomationStatus: string
{
    case PAUSED = 'paused';
    case ACTIVE = 'active';

    public function label(): string
    {
        return match ($this) {
            self::PAUSED => 'Paused',
            self::ACTIVE => 'Active',
        };
    }
}
