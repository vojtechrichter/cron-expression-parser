# Cron expression parser

## THIS RELEASE IS NOT STABLE AND NOT EFFICIENT

Cron expression parser with that supports all of the basic syntax with seconds support.

It is necessary to have set the default timezone, in order to get predictable results, with for example `date_default_timezone_set`.

Getting the next execution datetime:
```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('Europe/Prague');

try {
    $parser = \Vojtechrichter\CronExpressionParser\Parser::fromExpression('*/30 * * * * *');
    echo 'Next run: ' . $parser->getNextRun()->format('j.m.Y H:i:s') . PHP_EOL;
} catch (\Exception $e) {
    echo $e->getMessage();
}
```

Checking expression syntax validitiy:
```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('Europe/Prague');

$validator = new \Vojtechrichter\CronExpressionParser\ExpressionSyntaxValidator();

// returns bool value
var_dump($validator->isValid('0 0 9 * jan-dec mon-fri'));

// throws an exception on error
$validator->validate('0 0 9 * jan-dec mon-fri');
```
