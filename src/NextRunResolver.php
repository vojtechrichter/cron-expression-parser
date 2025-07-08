<?php

declare(strict_types=1);

namespace Vojtechrichter\CronExpressionParser;

final readonly class NextRunResolver
{
    /**
     * @param array<int, array<int>> $fields
     */
    public function __construct(
        private array $fields
    ) {
    }

    public function calculate(\DateTimeImmutable $from): \DateTimeImmutable
    {
        // Add one second to avoid returning current time
        $next = $from->add(new \DateInterval('PT1S'));
        $validator = new Validator($this->fields);

        for ($i = 0; $i < Parser::MAX_ITERATIONS; $i++) {
            if ($validator->isValid($next)) {
                return $next;
            }

            $next = $this->getNextRunCandidate($next);
        }

        throw new \RuntimeException('Could not find next execution time within 4 years');
    }

    private function getNextRunCandidate(\DateTimeImmutable $datetime): \DateTimeImmutable
    {
        $second = (int) $datetime->format('s');
        $minute = (int) $datetime->format('i');
        $hour = (int) $datetime->format('H');
        $day = (int) $datetime->format('j');
        $month = (int) $datetime->format('n');
        $year = (int) $datetime->format('Y');

        // Try to increment second
        $nextSecond = $this->getNextValue($second, $this->fields[FieldType::Second->value]);
        if ($nextSecond > $second) {
            return $datetime->setTime($hour, $minute, $nextSecond);
        }

        // Try to increment minute and reset seconds
        $nextMinute = $this->getNextValue($minute, $this->fields[FieldType::Minute->value]);
        if ($nextMinute > $minute) {
            return $datetime->setTime($hour, $nextMinute, $this->fields[FieldType::Second->value][0]);
        }

        // Try to increment hour and reset minutes & seconds
        $nextHour = $this->getNextValue($hour, $this->fields[FieldType::Hour->value]);
        if ($nextHour > $hour) {
            return $datetime->setTime($nextHour, $this->fields[FieldType::Minute->value][0], $this->fields[FieldType::Second->value][0]);
        }

        // Try to increment day and reset time
        $nextDay = $this->getNextValue($day, $this->fields[FieldType::Day->value]);
        if ($nextDay > $day && $nextDay <= cal_days_in_month(CAL_GREGORIAN, $month, $year)) {
            return $datetime->setDate($year, $month, $day)
                ->setTime($this->fields[FieldType::Hour->value][0], $this->fields[FieldType::Minute->value][0], $this->fields[FieldType::Second->value][0]);
        }

        // Increment year and reset everything
        $nextMonth = $this->getNextValue($month, $this->fields[FieldType::Month->value]);
        if ($nextMonth > $month) {
            return $datetime->setDate($year, $nextMonth, $this->fields[FieldType::Day->value][0])
                ->setTime($this->fields[FieldType::Hour->value][0], $this->fields[FieldType::Minute->value][0], $this->fields[FieldType::Second->value][0]);
        }

        return $datetime->setDate($year + 1, $this->fields[FieldType::Month->value][0], $this->fields[FieldType::Day->value][0])
            ->setTime($this->fields[FieldType::Hour->value][0], $this->fields[FieldType::Minute->value][0], $this->fields[FieldType::Second->value][0]);
    }

    /**
     * @param int $current
     * @param array<int> $validValues
     * @return int
     */
    private function getNextValue(int $current, array $validValues): int
    {
        sort($validValues);

        foreach ($validValues as $validValue) {
            if ($validValue > $current) {
                return $validValue;
            }
        }

        return $validValues[0];
    }
}
