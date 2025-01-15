<?php

use Rammewerk\Component\Environment\Environment;
use Rammewerk\Component\Environment\Validator;

require __DIR__ . '/vendor/autoload.php';

# Garbage collector
gc_collect_cycles();

function cacheFile(string $name): string {
    return __DIR__ . "/tests/cache/$name.json";
}


// Start time
$startTime = hrtime(true);

// Perform the loop
$loops = 100;

$env = new Environment();

for ($i = 0; $i < $loops; $i++) {
    $env->load(__DIR__ . '/tests/envFiles/strings.env');
    $env->load(__DIR__ . '/tests/envFiles/booleans.env');
    $env->load(__DIR__ . '/tests/envFiles/arrays.env');
    $env->load(__DIR__ . '/tests/envFiles/numbers.env');
    $env->validate(function (Validator $v) {
        $v->require('VALID_STRING')->notEmpty();
        $v->require('VALID_STRING')->endWith('id');
        $v->require('VALID_STRING')->allowedValues(['valid', 'whatever']);
        $v->require('VALID_LOWERCASE_TRUE')->isBoolean();
        $v->ifPresent('VALID_LOWERCASE_TRUE')->isBoolean();
        $v->ifPresent('VALID_ARRAY')->isArray();
        # Check that non-existing key is not required
        $v->ifPresent('NON_EXITING_KEY')->isArray();
        $v->require('VALID_INT')->isInteger();
    });
}

echo $env->getString('VALID_STRING');

// End time
$endTime = hrtime(true);

// Calculate elapsed time in milliseconds
$elapsedTimeMs = ($endTime - $startTime) / 1e6;
$elapsedTimeMs = round($elapsedTimeMs);

// Get peak memory usage in kilobytes
$peakMemoryKb = memory_get_peak_usage(true) / 1024;
$peakMemoryKb = number_format($peakMemoryKb, 0, ',', ' ');

echo "Elapsed Time: {$elapsedTimeMs} ms\n";
echo "Peak Memory Usage: {$peakMemoryKb} KB\n";

#phpinfo();