<?php

declare(strict_types=1);

namespace Vojtechrichter\CronExpressionParser;

final readonly class Parser
{
    private const array MONTHS_MAPPING = [
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12
    ];

    private const array WEEKDAYS_MAPPING = [
        'sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4,
        'fri' => 5, 'sat' => 6
    ];

    public const array RANGES = [
        FieldType::Second->value => [0, 59],
        FieldType::Minute->value => [0, 59],
        FieldType::Hour->value => [0, 23],
        FieldType::Day->value => [1, 31],
        FieldType::Month->value => [1, 12],
        FieldType::WeekDay->value => [0, 6]
    ];

    private const int MAX_ITERATIONS = 4 * 365 * 24 * 60 * 60;

    /**
     * @param array<int, array<int>> $fields
     */
    public function __construct(
        private array $fields
    ) {
    }

    public static function fromExpression(string $expression): self
    {
        return self::class;
    }
}
