<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Carbon;

enum CampaignFrequency: string
{
    case ONCE = 'once';
    case WEEKLY = 'weekly';
    case BIWEEKLY = 'biweekly';
    case MONTHLY = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::ONCE => 'Once',
            self::WEEKLY => 'Weekly',
            self::BIWEEKLY => 'Bi-weekly',
            self::MONTHLY => 'Monthly',
        };
    }

    /**
     * The next run after the given moment, or null for a one-off send.
     */
    public function nextRunAfter(Carbon $from): ?Carbon
    {
        return match ($this) {
            self::ONCE => null,
            self::WEEKLY => $from->copy()->addWeek(),
            self::BIWEEKLY => $from->copy()->addWeeks(2),
            self::MONTHLY => $from->copy()->addMonthNoOverflow(),
        };
    }
}
