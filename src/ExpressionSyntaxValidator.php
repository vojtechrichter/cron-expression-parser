<?php

declare(strict_types=1);

namespace Vojtechrichter\CronExpressionParser;

use Vojtechrichter\CronExpressionParser\Exceptions\CronExpressionException;

final readonly class ExpressionSyntaxValidator
{
    private const string VALID_CHARACTERS_REGEXP = '/^[0-9a-zA-Z*\/,-]+$/';

    public function validate(string $expression): void
    {
        $expression = trim($expression);

        if ($expression === '') {
            throw CronExpressionException::invalidSyntax('Expression cannot be empty');
        }

        /** @var array<string> $parts */ // @phpstan-ignore varTag.nativeType
        $parts = preg_split('/\s+/', $expression);

        if ($parts && count($parts) !== 6) {
            throw CronExpressionException::invalidFieldCount(count($parts));
        }

        for ($i = 0; $i < 6; $i++) {
            $this->validateFieldSyntax($parts[$i], FieldType::from($i));
        }
    }

    public function isValid(string $expression): bool
    {
        try {
            $this->validate($expression);

            return true;
        } catch (CronExpressionException $e) {
            return false;
        }
    }

    private function validateFieldSyntax(string $field, FieldType $fieldType): void
    {
        $fieldName = $fieldType->name;

        if ($field === '') {
            throw CronExpressionException::invalidSyntax('Field \'{$fieldName}\' cannot be empty');
        }

        if (!preg_match(self::VALID_CHARACTERS_REGEXP, $field)) {
            throw CronExpressionException::invalidSyntax('Field \'{$fieldName}\' contains invalid characters');
        }

        if (preg_match('/,,|^,|,$/', $field)) {
            throw CronExpressionException::invalidSyntax('Field \'{$fieldName}\' has malformed comma usage: \'{$field}\'');
        }

        $parts = explode(',', $field);
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                throw CronExpressionException::invalidSyntax('Empty part found in field \'{$fieldName}\'');
            }
            $this->validateFieldPart($part, $fieldType);
        }
    }

    private function validateFieldPart(string $part, FieldType $fieldType): void
    {
        if (str_contains($part, '/')) {
            $stepParts = explode('/', $part);

            if (count($stepParts) !== 2) {
                throw CronExpressionException::invalidSyntax('Invalid step syntax in field \'{$fieldName}\': \'{$part}\'');
            }

            [$range, $step] = $stepParts;

            if ($step === '') {
                throw CronExpressionException::invalidSyntax('Step value cannot be empty in field \'{$fieldName}\': \'{$part}\'');
            }

            if (!ctype_digit($step)) {
                throw CronExpressionException::invalidSyntax('Step value must be numeric in field \'{$fieldName}\': \'{$part}\'');
            }

            $stepValue = (int) $step;
            if ($stepValue <= 0) {
                throw CronExpressionException::invalidSyntax('Step value must be positive in field \'{$fieldName}\': \'{$part}\'');
            }

            if ($range === '') {
                throw CronExpressionException::invalidSyntax('Range cannot be empty before step in field \'{$fieldName}\': \'{$part}\'');
            }

            if ($range !== '*') {
                $this->validateRangeOrValue($range, $fieldType);
            }

            return;
        }

        $this->validateRangeOrValue($part, $fieldType);
    }

    private function validateRangeOrValue(string $part, FieldType $fieldType): void
    {
        $fieldName = $fieldType->name;

        if ($part === '*') {
            return;
        }

        if (str_contains($part, '-')) {
            $rangeParts = explode('-', $part);

            if (count($rangeParts) !== 2) {
                throw CronExpressionException::invalidSyntax('Invalid range syntax in field \'{$fieldName}\': \'{$part}\'');
            }

            [$start, $end] = $rangeParts;

            if ($start === '' || $end === '') {
                throw CronExpressionException::invalidSyntax('Range values cannot be empty in field \'{$fieldName}\': \'{$part}\'');
            }

            $this->validateSingleValue($start, $fieldType);
            $this->validateSingleValue($end, $fieldType);

            $startValue = $this->parseValueForValidation($start, $fieldType);
            $endValue = $this->parseValueForValidation($end, $fieldType);

            if ($startValue > $endValue) {
                throw CronExpressionException::invalidSyntax('Invalid range in field \'{$fieldName}\': start value {$startValue} is great than end value {$endValue}');
            }

            return;
        }

        $this->validateSingleValue($part, $fieldType);
    }

    private function validateSingleValue(string $value, FieldType $fieldType): void
    {
        $fieldName = $fieldType->name;

        if ($value === '') {
            throw CronExpressionException::invalidSyntax('Value cannot be empty in field \'{$fieldName}\'');
        }

        $lowerValue = strtolower($value);

        if ($fieldType->name === 'Month' && isset(Parser::MONTHS_MAPPING[$lowerValue])) {
            return;
        }

        if ($fieldType->name === 'WeekDay' && isset(Parser::WEEKDAYS_MAPPING[$lowerValue])) {
            return;
        }

        if (!ctype_digit($value)) {
            $validNames = '';

            if ($fieldType->name === 'Month') {
                $validNames = ' (valid names: ' . implode(', ', array_keys(Parser::MONTHS_MAPPING)) . ')';
            } else if ($fieldType->name === 'WeekDay') {
                $validNames = ' (valid names: ' . implode(', ', array_keys(Parser::WEEKDAYS_MAPPING)) . ')';
            }

            throw CronExpressionException::invalidSyntax('Invalid value in field \'{$fieldName}\': \'{$value}\'{$validNames}');
        }

        $intValue = (int) $value;
        $range = Parser::RANGES[$fieldType->value];

        if ($intValue < $range[0] || $intValue > $range[1]) {
            throw CronExpressionException::invalidSyntax('Value {$intValue} out of range [{$range[0]}-{$range[1]}] for field \'{$fieldName}\'');
        }
    }

    private function parseValueForValidation(string $value, FieldType $fieldType): int
    {
        $lowerValue = strtolower($value);

        if ($fieldType->name === 'Month' && isset(Parser::MONTHS_MAPPING[$lowerValue])) {
            return Parser::MONTHS_MAPPING[$lowerValue];
        }

        if ($fieldType->name === 'WeekDay' && isset(Parser::WEEKDAYS_MAPPING[$lowerValue])) {
            return Parser::WEEKDAYS_MAPPING[$lowerValue];
        }

        return (int) $value;
    }

    /**
     * @param FieldType $fieldType
     * @return array{names: array<string, int>, range: array<int, int>}
     */
    public function getFieldInfo(FieldType $fieldType): array
    {
        $info = [
            'name' => $fieldType->name,
            'range' => Parser::RANGES[$fieldType->value]
        ];

        if ($fieldType->name === 'Month') {
            $info['names'] = Parser::MONTHS_MAPPING;
        } else if ($fieldType->name === 'WeekDay') {
            $info['names'] = Parser::WEEKDAYS_MAPPING;
        }

        return $info;
    }
}
