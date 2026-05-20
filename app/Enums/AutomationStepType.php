<?php

declare(strict_types=1);

namespace App\Enums;

enum AutomationStepType: string
{
    case SEND_MAIL = 'send_mail';
    case WAIT = 'wait';
    case CONDITION = 'condition';
    case ADD_TAG = 'add_tag';
    case REMOVE_TAG = 'remove_tag';

    public function label(): string
    {
        return match ($this) {
            self::SEND_MAIL => 'Send Mail',
            self::WAIT => 'Wait',
            self::CONDITION => 'Condition',
            self::ADD_TAG => 'Add Tag',
            self::REMOVE_TAG => 'Remove Tag',
        };
    }
}
