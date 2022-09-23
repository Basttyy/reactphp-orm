<?php

require 'QueryBuilderWrapper.php';

use React\MySQL\Factory;
use React\MySQL\QueryResult;

require_once __DIR__ . "/../vendor/autoload.php";

$factory = new Factory();

$connection = (new QueryBuilderWrapper($factory))->createLazyConnection('root:123456789@localhost/trading-io');

$connection->from('users')->where('status', 'inactive')->query()->then(
    function (QueryResult $command) {
        print_r($command->resultRows);
        echo count($command->resultRows) . ' row(s) in set' . PHP_EOL;
    },
    function (Exception $error) {
        echo 'Error: ' . $error->getMessage() . PHP_EOL;
    }
);

$connection->quit();