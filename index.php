<?php

require __DIR__ . '/vendor/autoload.php';

$env = new \Rammewerk\Component\Environment\Environment();
$env->load( __DIR__ . '/tests/envFiles/strings.env' );

echo $env->getString( 'VALID_STRING' );

#phpinfo();