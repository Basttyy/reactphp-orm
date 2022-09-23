<?php
declare(strict_types=1);

require './vendor/autoload.php';
require './src/QueryBuilderWrapper.php';

use Basttyy\ReactphpOrm\QueryBuilderWrapper;
use Basttyy\ReactphpOrm\QueryBuilder;
use React\MySQL\Factory;
use React\MySQL\QueryResult;
use React\Promise\PromiseInterface;

$factory = new Factory();

$connection = (new QueryBuilderWrapper($factory))->createLazyConnection('root:123456789@localhost/trading-io');

$type = $argv[1] ? $argv[1] : 'query';

switch ($type) {
    case 'query':
        runQuery($connection);
        break;
    case 'runget':
        runGet($connection);
        break;
    default:
        runQuery($connection);
}

function runQuery(PromiseInterface|QueryBuilder $connection)
{
    $connection->from('users')->where('status', 'inactive')->query()->then(
        function (QueryResult $command) {
            print_r($command->resultRows);
            echo count($command->resultRows) . ' row(s) in set' . PHP_EOL;
        },
        function (Exception $error) {
            echo 'Error: ' . $error->getMessage() . PHP_EOL;
        }
    );
}

function runGet(PromiseInterface|QueryBuilder $connection)
{
    $data = $connection->from('users')->where('status', 'inactive')->get();

    print_r($data);
    //$connection->from('users')->where('status', 'inactive')->query()->then(
    //     function (QueryResult $command) {
    //         print_r($command->resultRows);
    //         echo count($command->resultRows) . ' row(s) in set' . PHP_EOL;
    //     },
    //     function (Exception $error) {
    //         echo 'Error: ' . $error->getMessage() . PHP_EOL;
    //     }
    // );
}

$connection->quit();