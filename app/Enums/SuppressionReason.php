<?php

declare(strict_types=1);

namespace App\Enums;

enum SuppressionReason: string
{
    case BOUNCE = 'bounce';
    case COMPLAINT = 'complaint';
    case MANUAL = 'manual';
    case UNSUBSCRIBE_ALL = 'unsubscribe_all';

    public function label(): string
    {
        return match ($this) {
            self::BOUNCE => 'Bounce',
            self::COMPLAINT => 'Complaint',
            self::MANUAL => 'Manual',
            self::UNSUBSCRIBE_ALL => 'Unsubscribe All',
        };
    }
}
