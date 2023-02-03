<?php
declare(strict_types=1);
namespace Basttyy\ReactphpOrm;

use Basttyy\ReactphpOrm\Query\Builder;
use Basttyy\ReactphpOrm\Query\Connection;
use Illuminate\Database\Capsule\Manager;
use React\MySQL\Factory;

class Wrapper
{
    private array $parts;

    private ?string $dbName;

    public function __construct(private Factory $factory, #[\SensitiveParameter] private string $uri)
    {
        if (strpos($this->uri, '://') === false) {
            $this->uri = 'mysql://' . $this->uri;
        }

        $this->parts = parse_url($this->uri);

        $this->dbName = isset($this->parts['path']) ? rawurldecode(ltrim($this->parts['path'], '/')) : null;

        if (is_null($this->dbName)) {
            throw new \InvalidArgumentException('Invalid Database name');
        }
    }

    private function createConnection(callable|LazyConnectionPool $callback)
    {
        $capsule = new Manager;

        $capsule->addConnection([
            'driver' => 'mysql',
            'database' => $this->parts['path'],
        ], $this->dbName);

        $builder = new Builder($capsule->getConnection());
        $connection = new Connection($callback);
        $builder->setConnection($connection);

        return $builder;
    }

    public function createLazyConnection()
    {
        return $this->createConnection($this->factory->createLazyConnection($this->uri));
    }

    public function createLazyConnectionPool(int $pool_size, string $connection_selector = LazyConnectionPool::CS_ROUND_ROBIN)
    {
        return $this->createConnection(new LazyConnectionPool($this->factory, $this->uri, $pool_size, $connection_selector));
    }
}
