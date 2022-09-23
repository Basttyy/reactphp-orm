<?php
declare(strict_types=1);

require './vendor/autoload.php';
require './src/QueryBuilderWrapper.php';

use Basttyy\ReactphpOrm\QueryBuilderWrapper;
use React\MySQL\Factory;
use React\MySQL\QueryResult;

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