<?php
declare(strict_types=1);
namespace Basttyy\ReactphpDatabase;

use Illuminate\Database\Capsule\Manager;
use React\MySQL\ConnectionInterface;
use React\MySQL\Factory;
use React\MySQL\QueryResult;
use Basttyy\ReactphpDatabase\QueryBuilder as Builder;

class QueryBuilderWrapper
{
    /**
     * @var Factory
     */
    public $factory;

    public function __construct($factory)
    {
        $this->factory = $factory;
    }

    public function createLazyConnection($uri)
    {
        $capsule = new Manager;

        if (strpos($uri, '://') === false) {
            $uri = 'mysql://' . $uri;
        }

        $parts = parse_url($uri);

        $dbName = isset($parts['path']) ? rawurldecode(ltrim($parts['path'], '/')) : null;

        if (is_null($dbName)) {
            return \React\Promise\reject(new \InvalidArgumentException(
                'Invalid Database name'
            ));
        }

        $capsule->addConnection([
            'driver' => 'mysql',
            'database' => $parts['path'],
        ]);

        $builder = new Builder($capsule->getConnection());
        //$builder = new QueryBuilder(new Connection($this->factory->createLazyConnection($uri)));
        $builder->setConnection($this->factory->createLazyConnection($uri));

        return $builder;
    }
}