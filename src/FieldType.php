<?php

declare(strict_types=1);

namespace Vojtechrichter\CronExpressionParser;

enum FieldType: int
{
    case Second = 0;
    case Minute = 1;
    case Hour = 2;
    case Day = 3;
    case Month = 4;
    case WeekDay = 5;
}
