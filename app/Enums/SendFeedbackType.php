<?php

declare(strict_types=1);

namespace App\Enums;

enum SendFeedbackType: string
{
    case OPEN = 'open';
    case CLICK = 'click';
    case BOUNCE = 'bounce';
    case COMPLAINT = 'complaint';
    case UNSUBSCRIBE = 'unsubscribe';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::CLICK => 'Click',
            self::BOUNCE => 'Bounce',
            self::COMPLAINT => 'Complaint',
            self::UNSUBSCRIBE => 'Unsubscribe',
        };
    }
}
