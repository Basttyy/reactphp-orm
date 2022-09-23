<?php
use Illuminate\Database\Query\Builder;
use React\MySQL\ConnectionInterface;

class QueryBuilder extends Builder
{
    /**
     * @var ConnectionInterface
     */
    private $_connection;

    public function query()
    {
        $sql = $this->toSql();
        echo $sql.PHP_EOL.PHP_EOL;
        return $this->_connection->query($sql, $this->getBindings());
    }

    public function quit()
    {
        $this->_connection->quit();
    }

    public function queryStream($sql)
    {
        $this->_connection->queryStream($sql);
    }

    public function setConnection(ConnectionInterface $connection)
    {
        $this->_connection = $connection;
    }
}