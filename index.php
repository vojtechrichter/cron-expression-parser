<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('Europe/Prague');

$validator = new \Vojtechrichter\CronExpressionParser\ExpressionSyntaxValidator();

// returns bool value
var_dump($validator->isValid('0 0 9 * jan-dec mon-fri'));

// throws an exception on error
$validator->validate('0 0 9 * jan-dec mon-fri');
