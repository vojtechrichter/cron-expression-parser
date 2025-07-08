<?php

declare(strict_types=1);

namespace Vojtechrichter\CronExpressionParser\Exceptions;

use Vojtechrichter\CronExpressionParser\FieldType;
use Vojtechrichter\CronExpressionParser\Parser;

final class CronExpressionException extends \InvalidArgumentException
{
    public static function invalidFieldCount(int $count): self
    {
        return new self(sprintf('Cron expression must have 6 fields, got %d', $count));
    }

    public static function valueOutOfRange(int $value, FieldType $fieldType): self
    {
        $allowedRange = Parser::RANGES[$fieldType->value];

        return new self(sprintf('Value %d is out of range [%d-%d] for field type %d', $value, $allowedRange[0], $allowedRange[1], $fieldType->name));
    }
}
