<?php
declare(strict_types=1);

require './vendor/autoload.php';

use Basttyy\ReactphpOrm\QueryBuilderWrapper;
use Basttyy\ReactphpOrm\QueryBuilder;
use Illuminate\Support\Collection;
use React\MySQL\Factory;
use React\MySQL\QueryResult;
use React\Promise\PromiseInterface;

$factory = new Factory();

$connection = (new QueryBuilderWrapper($factory))->createLazyConnectionPool('root:123456789@localhost/react-database', 5);

$type = isset($argv[1]) ? $argv[1] : 'query';

switch ($type) {
    case 'runfind':
        runFind($connection);
        break;
    case 'runexists':
        runExists($connection);
        break;
    case 'runcount':
        runCount($connection);
        break;
    case 'query':
        runQuery($connection);
        break;
    case 'runget':
        runGet($connection);
        break;
    case 'runfirst':
        runFirst($connection);
        break;
    case 'insert':
        runInsert($connection,  isset($argv[2]) ? $argv[2] : 'false');
        break;
    case 'runupdate':
        runUpdate($connection);
        break;
    case 'rundelete':
        runDelete($connection);
        break;
    default:
        runQuery($connection);
}

function runFind(PromiseInterface|QueryBuilder $connection)
{
    $connection->from('users')->find(3)->then(
        function ($result) {
            print_r($result);
            echo count($result) . ' columns(s) in set' . PHP_EOL;
        },
        function (Exception $error) {
            echo 'Error: ' . $error->getMessage() . PHP_EOL;
        }
    );
}

function runExists(PromiseInterface|QueryBuilder $connection)
{
    $connection->from('users')->where('deleted_at', '!=', null)->exists()->then(
        function (bool $result) {
            echo $result ? 'record exists in database' : 'record does not exist in database' . PHP_EOL;
        },
        function (Exception $error) {
            echo 'Error: ' . $error->getMessage() . PHP_EOL;
        }
    );
}

function runCount(PromiseInterface|QueryBuilder $connection)
{
    $connection->from('users')->where('username', 'basttyy')->count()->then(
        function (int $result) {
            echo "$result total records matched" . PHP_EOL;
        },
        function (Exception $error) {
            echo 'Error: ' . $error->getMessage() . PHP_EOL;
        }
    );
}

function runQuery(PromiseInterface|QueryBuilder $connection)
{
    $connection->from('users')->where('status', 'active')->query()->then(
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
    $connection->from('users')->where('status', 'active')->get()->then(
        function(Collection $data) {
            print_r($data->all());
            echo $data->count() . ' row(s) in set' . PHP_EOL;
        },
        function (Exception $error) {
            echo 'Error: ' . $error->getMessage() . PHP_EOL;
        }
    );
}

function runFirst(PromiseInterface|QueryBuilder $connection)
{
    $connection->from('users')->where('updated_at', null)->first()->then(
        function (array $resultRows) {
            print_r($resultRows);
            echo count($resultRows) . ' columns(s) in set' . PHP_EOL;
        },
        function (Exception $ex) {
            echo $ex->getMessage().PHP_EOL;
        }
    );
}

function runInsert(PromiseInterface|QueryBuilder $connection, string $getid)
{
    $values = [
        'username' => 'basttyy',
        'firstname' => 'abdulbasit',
        'lastname' => 'mamman',
        'email' => 'basttyydev@mail.com'
    ];
    
    if ($getid === "true") {
        $connection->from('users')->insertGetId($values)->then(
            function (int $id) {
                echo "inserted successfully with ID: ".$id.PHP_EOL;
            },
            function (Exception $ex) {
                echo $ex->getMessage().PHP_EOL;
            }
        );
    } else {
        $connection->from('users')->insert($values)->then(
            function (bool $status) {
                echo "inserted successfully ".PHP_EOL;
            },
            function (Exception $ex) {
                echo $ex->getMessage().PHP_EOL;
            }
        );
    }
}

function runUpdate(PromiseInterface|QueryBuilder $connection)
{
    $values = [
        'username' => 'bushman',
        'firstname' => 'abdulbasit',
    ];

    $connection->from('users')->where('id', 9)->update($values)->then(
        function (int $result) {
            echo "updated $result records successfully".PHP_EOL;
        },
        function (Exception $ex) {
            echo $ex->getMessage().PHP_EOL;
        }
    );
}

function runDelete(PromiseInterface|QueryBuilder $connection)
{
    $connection->from('users')->where('id', 9)->delete()->then(
        function (int $result) {
            echo "deleted $result record successfully".PHP_EOL;
        },
        function (Exception $ex) {
            echo $ex->getMessage().PHP_EOL;
        }
    );
    $connection->from('users')->order
}

$connection->quit();