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
            FieldType::Second->value =>
        ]
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
        // TODO: implement
    }
}
