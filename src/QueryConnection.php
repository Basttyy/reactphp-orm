<?php
declare(strict_types=1);
namespace Basttyy\ReactphpOrm;

use Closure;
use Exception;
use Illuminate\Database\Connection;
use React\MySQL\Exception as QueryException;
use React\MySQL\ConnectionInterface;
use React\MySQL\QueryResult;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Basttyy\ReactphpOrm\QueryProcessor as Processor;

class QueryConnection extends Connection
{
    /**
     * The active PDO connection.
     *
     * @var ConnectionInterface|\Closure
     */
    protected $pdo;

    
    /**
     * The query post processor implementation.
     *
     * @var Processor
     */
    protected $postProcessor;

    /**
     * Create a new database connection instance.
     *
     * @param  \React\MySQL\ConnectionInterface|\Closure  $pdo
     * @param  string  $database
     * @param  string  $tablePrefix
     * @param  array  $config
     * @return void
     */
    public function __construct(ConnectionInterface|\Closure $pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        $this->pdo = $pdo;

        // First we will setup the default properties. We keep track of the DB
        // name we are connected to since it is needed when some reflective
        // type commands are run such as checking whether a table exists.
        $this->database = $database;

        $this->tablePrefix = $tablePrefix;

        $this->config = $config;

        // We need to initialize a query grammar and the query post processors
        // which are both very important parts of the database abstractions
        // so we initialize these to their default values while starting.
        $this->useDefaultQueryGrammar();

        $this->useDefaultPostProcessor();
    }

    /**
     * Get the current Database connection.
     *
     * @return \React\MySQL\ConnectionInterface
     */
    public function getConnection()
    {
        if ($this->pdo instanceof Closure) {
            return $this->pdo = call_user_func($this->pdo);
        }

        return $this->pdo;
    }

    /**
     * Run an insert statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return PromiseInterface<bool>
     */
    public function insert($query, $bindings = [])
    {
        return $this->statement($query, $bindings);
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return PromiseInterface<bool|int|Exception>
     */
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return \React\Promise\resolve(true);
            }

            $deffered = new Deferred();
            $this->makeQuery($query, $this->prepareBindings($bindings))->then(
                function (QueryResult $command) use ($deffered) {
                    echo "query executed success".PHP_EOL;
                    $this->recordsHaveBeenModified();
                    if (isset($command->resultRows)) {
                        // this is a response to a SELECT etc. with some rows (0+)
                        print_r($command->resultRows);
                        echo count($command->resultRows) . ' row(s) in set' . PHP_EOL;
                        $deffered->resolve(true);
                    } else {
                        // this is an OK message in response to an UPDATE etc.
                        if ($command->insertId !== 0) {
                            var_dump('last insert ID', $command->insertId);
                        }
                        echo 'Query OK, ' . $command->affectedRows . ' row(s) affected' . PHP_EOL;
                        $status = $command->insertId < 1 ? false : $command->insertId;
                        $deffered->resolve($status);
                    }
                },
                function (Exception $error) use ($deffered) {
                    echo 'Error: ' . $error->getMessage() . PHP_EOL;
                    $deffered->reject($error);
                }
            );
            return $deffered->promise();
        });
    }

    /**
     * Run a SQL statement and log its execution context.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws PromiseInterface<bool|QueryException>
     */
    protected function run($query, $bindings, Closure $callback)
    {
        foreach ($this->beforeExecutingCallbacks as $beforeExecutingCallback) {
            $beforeExecutingCallback($query, $bindings, $this);
        }

        //$this->reconnectIfMissingConnection();

        $start = microtime(true);

        // Here we will run this query. If an exception occurs we'll determine if it was
        // caused by a connection that has been lost. If that is the cause, we'll try
        // to re-establish connection and re-run the query with a fresh connection.
        try {
            $result = $this->runQueryCallback($query, $bindings, $callback);
        } catch (QueryException $e) {
            \React\Promise\reject($e);
        }

        // Once we have run the query we will calculate the time that it took to run and
        // then log the query, bindings, and execution time so we will report them on
        // the event that the developer needs them. We'll log time in milliseconds.
        $this->logQuery(
            $query, $bindings, $this->getElapsedTime($start)
        );

        return $result;
    }
    
    /**
     * Run a SQL statement.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  \Closure  $callback
     * @return PromiseInterface
     */
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        // To execute the statement, we'll simply call the callback, which will actually
        // run the SQL against the PDO connection. Then we can calculate the time it
        // took to execute and log the query SQL, bindings and time in our memory.
        try {
            return $callback($query, $bindings);
        }

        // If an exception occurs when attempting to run a query, we'll format the error
        // message to include the bindings with SQL, which will make this exception a
        // lot more helpful to the developer instead of just the database's errors.
        catch (QueryException $e) {
            \React\Promise\reject($e);
        }
    }

    /**
     * Run a query against the db and get results
     * @param string $sql    SQL statement
     * @param array  $bindings Parameters which should be bound to query
     * @return PromiseInterface<QueryResult|Exception> Returns a Promise<QueryResult,Exception>
     */
    public function makeQuery($sql, $bindings)
    {
        return $this->getConnection()->query($sql, $bindings);
    }
    
    /**
     * Get a new query builder instance.
     *
     * @return QueryBuilder
     */
    public function query()
    {
        return new QueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * Quit the connection to the db
     * 
     * @var void
     * @return PromiseInterface
     */
    public function quit()
    {
        $this->getConnection()->quit();
    }

    /**
     * Get the query post processor used by the connection.
     *
     * @return Processor
     */
    public function getPostProcessor()
    {
        return $this->postProcessor;
    }

    /**
     * Set the query post processor to the default implementation.
     *
     * @return void
     */
    public function useDefaultPostProcessor()
    {
        $this->postProcessor = $this->getDefaultPostProcessor();
    }

    /**
     * Get the default post processor instance.
     *
     * @return Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new Processor;
    }

    /**
     * Handle a query exception.
     *
     * @param  QueryException  $e
     * @param  string  $query
     * @param  array  $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws QueryException
     */
    // protected function handleQueryException(QueryException $e, $query, $bindings, Closure $callback)
    // {
    //     if ($this->transactions >= 1) {
    //         throw $e;
    //     }

    //     return $this->tryAgainIfCausedByLostConnection(
    //         $e, $query, $bindings, $callback
    //     );
    // }

    /**
     * Handle a query exception that occurred during query execution.
     *
     * @param  QueryException  $e
     * @param  string  $query
     * @param  array  $bindings
     * @param  \Closure  $callback
     * @return PromiseInterface<mixed>
     */
    // protected function tryAgainIfCausedByLostConnection(QueryException $e, $query, $bindings, Closure $callback)
    // {
    //     if ($this->causedByLostConnection($e->getPrevious())) {
    //         $this->reconnect();

    //         return $this->runQueryCallback($query, $bindings, $callback);
    //     }

    //     \React\Promise\reject($e);
    // }
}