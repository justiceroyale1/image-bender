<?php

declare(strict_types=1);

namespace App\Enums;

enum ReasoningLevel: string
{
    case Min = 'min';
    case Max = 'max';
}
