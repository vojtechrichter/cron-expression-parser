<?php

declare(strict_types=1);

namespace Vojtechrichter\CronExpressionParser;

final readonly class Validator
{
    public function __construct(
        private array $fields
    ) {
    }

    public function isValid(\DateTimeImmutable $datetime): bool
    {
        return in_array((int) $datetime->format('s'), $this->fields[FieldType::Second->value], true) &&
            in_array((int) $datetime->format('i'), $this->fields[FieldType::Minute->value], true) &&
            in_array((int) $datetime->format('H'), $this->fields[FieldType::Hour->value], true) &&
            in_array((int) $datetime->format('j'), $this->fields[FieldType::Day->value], true) &&
            in_array((int) $datetime->format('n'), $this->fields[FieldType::Month->value], true) &&
            in_array((int) $datetime->format('w'), $this->fields[FieldType::WeekDay->value], true);
    }
}
