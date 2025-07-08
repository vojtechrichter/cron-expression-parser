<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('Europe/Prague');

try {
    $parser = \Vojtechrichter\CronExpressionParser\Parser::fromExpression('*/30 * * * * *');
    echo 'Next run: ' . $parser->getNextRun()->format('j.m.Y H:i:s') . PHP_EOL;
} catch (\RuntimeException $e) {
    echo 'Next run: ' . $e->getMessage() . PHP_EOL;
}
