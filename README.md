# Reactphp-ORM

 A database ORM for reactphp based on [illuminate/database](https://packagist.org/packages/illuminate/database) and [react/mysql](https://packagist.org/packages/react/mysql)

## Project Informations

[![Code Coverage](https://img.shields.io/badge/coverage-5%25-orange)]()

## Installation

Use php package manager [composer](https://getcomposer.org/download/) to install reactphp-orm.

```bash
composer require basttyy/reactphp-orm
```

## Usage

```php
<?php
//import
require './vendor/autoload.php';

use Basttyy\ReactphpOrm\QueryBuilderWrapper;
use Basttyy\ReactphpOrm\QueryBuilder;
use React\MySQL\Factory;
use React\MySQL\QueryResult;

#create react/mysql factory
$factory = new Factory();

#create querybuilder connection object
$connection = (new QueryBuilderWrapper($factory))->createLazyConnection('root:123456789@localhost/react-database');

#run an insert query
$values = [
    'username' => 'johndoe',
    'firstname' => 'john',
    'lastname' => 'doe',
    'email' => 'johndoe@mail.com'
];

$connection->from('users')->insert($values)->then(
    function (bool $status) {
        echo "inserted successfully ".PHP_EOL;
    },
    function (Exception $ex) {
        echo $ex->getMessage().PHP_EOL;
    }
);

#run a select where query
$connection->from('users')->where('status', 'active')->query()->then(
    function (QueryResult $command) {
        print_r($command->resultRows);
        echo count($command->resultRows) . ' row(s) in set' . PHP_EOL;
    },
    function (Exception $error) {
        echo 'Error: ' . $error->getMessage() . PHP_EOL;
    }
);

#run a get query
$connection->from('users')->where('status', 'active')->get()->then(
    function(Collection $data) {
        print_r($data->all());
        echo $data->count() . ' row(s) in set' . PHP_EOL;
    },
    function (Exception $error) {
        echo 'Error: ' . $error->getMessage() . PHP_EOL;
    }
);

```
## Query Features Coverage Map

 * [x] query()
 * [x] get()
 * [x] insert()
 * [ ] delete
 * [ ] Update
 * [ ] first()
 * [ ] find()
 * [ ] count
 * [ ] exists
 * [ ] InsertOrIgnore
 * [ ] InsertUsing
 * [ ] UpdateOrInsert
 * [ ] UpdateFrom
 * [ ] Upsert
 * [ ] pluck
 * [ ] doesntexist
 * [ ] existsor
 * [ ] doesntexistor
 * [ ] Increment
 * [ ] decrement
 * [ ] lock
 * [ ] lockforupdate
 * [ ] findor
 * [ ] value
 * [ ] paginate
 * [ ] simplepaginate
 * [ ] cursopaginate
 * [ ] getcountforpagination
 * [ ] getpaginationcountquery
 * [ ] cursor
 * [ ] min
 * [ ] max
 * [ ] sum
 * [ ] avg
 * [ ] average
 * [ ] aggregate
 * [ ] numericaggregate
 * [ ] truncate
 * [ ] newQuery
 * [ ] forSubQuery
 * [ ] raw
 * [ ] getProcessor
 * [ ] useWritePDO
 * [ ] clone
 * [ ] cloneWithout
 * [ ] cloneWithoutBindings

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)