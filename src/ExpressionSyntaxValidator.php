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

        $parts = preg_split('/\s+/', $expression);

        if (count($parts) !== 6) {
            throw CronExpressionException::invalidFieldCount(count($parts));
        }

        for ($i = 0; $i < 6; $i++) {
            $this->validateFieldSyntax($parts[$i], FieldType::from($i));
        }
    }

    private function validateFieldSyntax(string $field, FieldType $fieldType): void
    {
        $fieldName = $fieldType->name;

        if ($field !== '') {
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

    }
}
