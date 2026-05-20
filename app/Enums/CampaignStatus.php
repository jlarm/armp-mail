<?php

declare(strict_types=1);

namespace App\Enums;

enum CampaignStatus: string
{
    case DRAFT = 'draft';
    case SENDING = 'sending';
    case SENT = 'sent';
    case CANCELLED = 'cancelled';
    case PAUSED = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENDING => 'Sending',
            self::SENT => 'Sent',
            self::CANCELLED => 'Cancelled',
            self::PAUSED => 'Paused',
        };
    }
}
