<?php
declare(strict_types=1);
namespace Basttyy\ReactphpOrm;

use Illuminate\Database\Capsule\Manager;
use React\MySQL\ConnectionInterface;
use React\MySQL\Factory;
use React\MySQL\QueryResult;

class QueryBuilderWrapper
{
    /**
     * @var Factory
     */
    public $factory;

    public function __construct($factory = null)
    {
        if (!\is_null($factory))
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

        $builder = new QueryBuilder($capsule->getConnection());
        //$builder = new QueryBuilder(new Connection($this->factory->createLazyConnection($uri)));
        $connection = new QueryConnection($this->factory->createLazyConnection($uri));
        $builder->setConnection($connection);

        return $builder;
    }
    
    public function createLazyConnectionPool(string $uri, int $pool_size, $connection_selector = LazyConnectionPool::CS_ROUND_ROBIN)
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

        $builder = new QueryBuilder($capsule->getConnection());
        //$builder = new QueryBuilder(new Connection($this->factory->createLazyConnection($uri)));
        $connection = new QueryConnection(new LazyConnectionPool($this->factory, $uri, $pool_size, $connection_selector));
        $builder->setConnection($connection);

        return $builder;
    }

    public function setLazyConnectionPool(ConnectionInterface $connection, string $db_name = '')
    {
        return $this->setLazyConnection($connection, $db_name);
    }

    public function setLazyConnection(ConnectionInterface $connection, string $db_name = '')
    {
        $capsule = new Manager;

        $capsule->addConnection([
            'driver' => 'mysql',
            'database' => $db_name,
        ]);

        $builder = new QueryBuilder($capsule->getConnection());
        $connection = new QueryConnection($connection);
        $builder->setConnection($connection);

        return $builder;
    }
}