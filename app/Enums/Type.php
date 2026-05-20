<?php

declare(strict_types=1);

namespace App\Enums;

enum Type: string
{
    case TEXT = 'text';
    case NUMBER = 'number';
    case DATE = 'date';
    case BOOLEAN = 'boolean';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Text',
            self::NUMBER => 'Number',
            self::DATE => 'Date',
            self::BOOLEAN => 'Boolean',
        };
    }
}
