<?php

declare(strict_types=1);

namespace Vojtechrichter\CronExpressionParser;

use Vojtechrichter\CronExpressionParser\Exceptions\CronExpressionException;

final readonly class InternalParser
{
    public function parse(string $expression): array
    {
        $parts = preg_split('/\s+/', trim($expression));

        if (count($parts) !== 6) {
            throw CronExpressionException::invalidFieldCount(count($parts));
        }

        return [
            FieldType::Second->value => $this->parseField($parts[0], FieldType::Second),
            FieldType::Minute->value => $this->parseField($parts[1], FieldType::Minute),
            FieldType::Hour->value => $this->parseField($parts[2], FieldType::Hour),
            FieldType::Day->value => $this->parseField($parts[3], FieldType::Day),
            FieldType::Month->value => $this->parseField($parts[4], FieldType::Month),
            FieldType::WeekDay->value => $this->parseField($parts[5], FieldType::WeekDay)
        ];
    }

    /**
     * @param string $field
     * @param FieldType $type
     * @return array<int>
     */
    private function parseField(string $field, FieldType $type): array
    {
        if ($field === '*') {
            return range(Parser::RANGES[$type->value][0], Parser::RANGES[$type->value][1]);
        }

        $values = [];
        $parts = explode(',', $field);

        foreach ($parts as $part) {
            $values = [...$values, $this->parseFieldPart($part, $type)];
        }

        return array_unique($values);
    }

    /**
     * @param string $part
     * @param FieldType $type
     * @return array<int>
     */
    private function parseFieldPart(string $part, FieldType $type): array
    {
        if (str_contains($part, '/')) {
            [$range, $step] = explode('/', $part, 2);
            $step = (int) $step;

            if ($range === '*') {
                $start = Parser::RANGES[$type->value][0];
                $end = Parser::RANGES[$type->value][1];
            } else if (str_contains($part, '-')) {
                [$start, $end] = explode('-', $part, 2);
                $start = $this->parseValue($start, $type);
                $end = $this->parseValue($end, $type);
            } else {
                $start = $this->parseValue($part, $type);
                $end = Parser::RANGES[$type->value][1];
            }

            $values = [];
            for ($i = $start; $i <= $end; $i += $step) {
                $values[] = $i;
            }

            return $values;
        }

        if (str_contains($part, '-')) {
            [$start, $end] = explode('-', $part, 2);
            $start = $this->parseValue($part, $type);
            $end = $this->parseValue($part, $type);

            return range($start, $end);
        }

        return [$this->parseValue($part, $type)];
    }

    private function parseValue(string $value, FieldType $type): int
    {
        $value = strtolower($value);

        if ($type->value === FieldType::Month->value && isset(Parser::MONTHS_MAPPING[$value])) {
            return Parser::MONTHS_MAPPING[$value];
        }

        if ($type->value === FieldType::WeekDay->value && isset(Parser::WEEKDAYS_MAPPING[$value])) {
            return Parser::WEEKDAYS_MAPPING[$value];
        }

        $intValue = (int) $value;

        if ($intValue < Parser::RANGES[$type->value][0] || $intValue > Parser::RANGES[$type->value][1]) {
            throw CronExpressionException::valueOutOfRange($intValue, $type);
        }

        return $intValue;
    }
}
