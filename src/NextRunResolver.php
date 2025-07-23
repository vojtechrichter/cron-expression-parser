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

        $maxIterations = 100;

        for ($i = 0; $i < $maxIterations; $i++) {
            if ($validator->isValid($next)) {
                return $next;
            }

            $next = $this->getNextRunCandidate($next);
        }

        throw new \RuntimeException('Could not find next execution time within reasonable iterations');
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
            return $datetime->setTime($hour, $nextMinute, $this->getFirstValue($this->fields[FieldType::Second->value]));
        }

        // Try to increment hour and reset minutes & seconds
        $nextHour = $this->getNextValue($hour, $this->fields[FieldType::Hour->value]);
        if ($nextHour > $hour) {
            return $datetime->setTime(
                $nextHour,
                $this->getFirstValue($this->fields[FieldType::Minute->value]),
                $this->getFirstValue($this->fields[FieldType::Second->value])
            );
        }

        // Try to increment day and reset time
        $nextDay = $this->getNextValue($day, $this->fields[FieldType::Day->value]);
        if ($nextDay > $day && $nextDay <= cal_days_in_month(CAL_GREGORIAN, $month, $year)) {
            return $datetime->setDate($year, $month, $nextDay)
                ->setTime(
                    $this->getFirstValue($this->fields[FieldType::Hour->value]),
                    $this->getFirstValue($this->fields[FieldType::Minute->value]),
                    $this->getFirstValue($this->fields[FieldType::Second->value])
                );
        }

        // Try to increment month and reset day & time
        $nextMonth = $this->getNextValue($month, $this->fields[FieldType::Month->value]);
        if ($nextMonth > $month) {
            $validDay = $this->getValidDayForMonth($year, $nextMonth);
            return $datetime->setDate($year, $nextMonth, $validDay)
                ->setTime(
                    $this->getFirstValue($this->fields[FieldType::Hour->value]),
                    $this->getFirstValue($this->fields[FieldType::Minute->value]),
                    $this->getFirstValue($this->fields[FieldType::Second->value])
                );
        }

        // Increment year and reset everything
        $nextYear = $year + 1;
        $firstMonth = $this->getFirstValue($this->fields[FieldType::Month->value]);
        $validDay = $this->getValidDayForMonth($nextYear, $firstMonth);

        return $datetime->setDate($nextYear, $firstMonth, $validDay)
            ->setTime(
                $this->getFirstValue($this->fields[FieldType::Hour->value]),
                $this->getFirstValue($this->fields[FieldType::Minute->value]),
                $this->getFirstValue($this->fields[FieldType::Second->value])
            );
    }

    private function getValidDayForMonth(int $year, int $month): int
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $validDays = $this->fields[FieldType::Day->value];

        sort($validDays);

        foreach ($validDays as $day) {
            if ($day <= $daysInMonth) {
                return $day;
            }
        }

        return $validDays[0];
    }

    /**
     * Get the first (smallest) valid value from an array
     */
    private function getFirstValue(array $validValues): int
    {
        $sorted = $validValues;
        sort($sorted);
        return $sorted[0];
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

        return $current; // Return current if no next value found (signals need to increment higher field)
    }
}
