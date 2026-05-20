<?php

declare(strict_types=1);

namespace App\Enums;

enum Status: string
{
    case UNCONFIRMED = 'unconfirmed';
    case SUBSCRIBED = 'subscribed';
    case UNSUBSCRIBED = 'unsubscribed';
    case BOUNCED = 'bounced';
    case COMPLAINED = 'complained';

    public function label(): string
    {
        return match ($this) {
            self::UNCONFIRMED => 'Unconfirmed',
            self::SUBSCRIBED => 'Subscribed',
            self::UNSUBSCRIBED => 'Unsubscribed',
            self::BOUNCED => 'Bounced',
            self::COMPLAINED => 'Complained',
        };
    }
}
