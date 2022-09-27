<?php
declare(strict_types=1);
namespace Basttyy\ReactphpOrm;

use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use React\MySQL\ConnectionInterface;
use React\MySQL\QueryResult;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

use function PHPSTORM_META\map;

class QueryBuilder extends Builder
{
    /**
     * @var \Illuminate\Database\ConnectionInterface|QueryConnection
     */
    private $_connection;

    /**
     * The database query post processor instance.
     *
     * @var \Basttyy\ReactphpOrm\QueryProcessor
     */
    public $processor;

    // /**
    //  * Create a new query builder instance.
    //  *
    //  * @param  \Illuminate\Database\ConnectionInterface|ConnectionInterface  $connection
    //  * @param  \Illuminate\Database\Query\Grammars\Grammar|null  $grammar
    //  * @param  \Basttyy\ReactphpOrm\Processor|null  $processor
    //  * @return void
    //  */
    // public function __construct(\Illuminate\Database\ConnectionInterface|ConnectionInterface $connection,
    //                             Grammar $grammar = null,
    //                             Processor $processor = null)
    // {
    //     $this->connection = $connection;
    //     $this->grammar = $grammar ?: $connection->getQueryGrammar();
    //     $this->processor = $processor ?: $connection->getPostProcessor();
    // }

    /**
     * Create a new query builder instance.
     *
     * @param  \Illuminate\Database\ConnectionInterface|QueryConnection  $connection
     * @param  \Illuminate\Database\Query\Grammars\Grammar|null  $grammar
     * @param  QueryProcessor|null  $processor
     * @return void
     */
    public function __construct(\Illuminate\Database\ConnectionInterface|QueryConnection $connection,
                                Grammar $grammar = null,
                                QueryProcessor $processor = null)
    {
        $this->_connection = $connection;
        $this->grammar = $grammar ?: $connection->getQueryGrammar();
    }

    /**
     * Quit the connection to the db
     * 
     * @var void
     * @return PromiseInterface
     */
    public function quit()
    {
        $this->_connection->quit();
    }

    /**
     * Performs an async query and streams the rows of the result set.
     *
     * This method returns a readable stream that will emit each row of the
     * result set as a `data` event. It will only buffer data to complete a
     * single row in memory and will not store the whole result set. This allows
     * you to process result sets of unlimited size that would not otherwise fit
     * into memory. If you know your result set to not exceed a few dozens or
     * hundreds of rows, you may want to use the [`query()`](#query) method instead.
     *
     * ```php
     * $stream = $connection->queryStream('SELECT * FROM user');
     * $stream->on('data', function ($row) {
     *     echo $row['name'] . PHP_EOL;
     * });
     * $stream->on('end', function () {
     *     echo 'Completed.';
     * });
     * ```
     *
     * You can optionally pass an array of `$params` that will be bound to the
     * query like this:
     *
     * ```php
     * $stream = $connection->queryStream('SELECT * FROM user WHERE id > ?', [$id]);
     * ```
     *
     * This method is specifically designed for queries that return a result set
     * (such as from a `SELECT` or `EXPLAIN` statement). Queries that do not
     * return a result set (such as a `UPDATE` or `INSERT` statement) will not
     * emit any `data` events.
     *
     * See also [`ReadableStreamInterface`](https://github.com/reactphp/stream#readablestreaminterface)
     * for more details about how readable streams can be used in ReactPHP. For
     * example, you can also use its `pipe()` method to forward the result set
     * rows to a [`WritableStreamInterface`](https://github.com/reactphp/stream#writablestreaminterface)
     * like this:
     *
     * ```php
     * $connection->queryStream('SELECT * FROM user')->pipe($formatter)->pipe($logger);
     * ```
     *
     * Note that as per the underlying stream definition, calling `pause()` and
     * `resume()` on this stream is advisory-only, i.e. the stream MAY continue
     * emitting some data until the underlying network buffer is drained. Also
     * notice that the server side limits how long a connection is allowed to be
     * in a state that has outgoing data. Special care should be taken to ensure
     * the stream is resumed in time. This implies that using `pipe()` with a
     * slow destination stream may cause the connection to abort after a while.
     *
     * The given `$sql` parameter MUST contain a single statement. Support
     * for multiple statements is disabled for security reasons because it
     * could allow for possible SQL injection attacks and this API is not
     * suited for exposing multiple possible results.
     *
     * @param string $sql    SQL statement
     * @param array  $params Parameters which should be bound to query
     * @return ReadableStreamInterface
     */
    public function queryStream($sql)
    {
        $this->_connection->queryStream($sql);
    }

    /**
     * Set the connetion object
     * 
     * @param QueryConnection $connection
     * @return void
     */
    public function setConnection(QueryConnection $connection)
    {
        $this->_connection = $connection;
        $this->processor = $this->_connection->getPostProcessor();
    }

    /**
     * Get the connetion object
     * 
     * @param void
     * @return QueryConnection
     */
    public function getConnection()
    {
        return $this->_connection;
    }

      /**
     * Performs an async query.
     *
     * This method returns a promise that will resolve with a `QueryResult` on
     * success or will reject with an `Exception` on error. The MySQL protocol
     * is inherently sequential, so that all queries will be performed in order
     * and outstanding queries will be put into a queue to be executed once the
     * previous queries are completed.
     *
     * ```php
     * $connection->query('CREATE TABLE test ...');
     * $connection->query('INSERT INTO test (id) VALUES (1)');
     * ```
     *
     * If this SQL statement returns a result set (such as from a `SELECT`
     * statement), this method will buffer everything in memory until the result
     * set is completed and will then resolve the resulting promise. This is
     * the preferred method if you know your result set to not exceed a few
     * dozens or hundreds of rows. If the size of your result set is either
     * unknown or known to be too large to fit into memory, you should use the
     * [`queryStream()`](#querystream) method instead.
     *
     * ```php
     * $connection->query($query)->then(function (QueryResult $command) {
     *     if (isset($command->resultRows)) {
     *         // this is a response to a SELECT etc. with some rows (0+)
     *         print_r($command->resultFields);
     *         print_r($command->resultRows);
     *         echo count($command->resultRows) . ' row(s) in set' . PHP_EOL;
     *     } else {
     *         // this is an OK message in response to an UPDATE etc.
     *         if ($command->insertId !== 0) {
     *             var_dump('last insert ID', $command->insertId);
     *         }
     *         echo 'Query OK, ' . $command->affectedRows . ' row(s) affected' . PHP_EOL;
     *     }
     * }, function (Exception $error) {
     *     // the query was not executed successfully
     *     echo 'Error: ' . $error->getMessage() . PHP_EOL;
     * });
     * ```
     *
     * You can optionally pass an array of `$params` that will be bound to the
     * query like this:
     *
     * ```php
     * $connection->query('SELECT * FROM user WHERE id > ?', [$id]);
     * ```
     *
     * The given `$sql` parameter MUST contain a single statement. Support
     * for multiple statements is disabled for security reasons because it
     * could allow for possible SQL injection attacks and this API is not
     * suited for exposing multiple possible results.
     *
     * @param string $sql    SQL statement
     * @param array  $params Parameters which should be bound to query
     * @return PromiseInterface<QueryResult|Exception> Returns a Promise<QueryResult,Exception>
     */
    public function query()
    {
        $sql = $this->toSql();
        echo $sql.PHP_EOL.PHP_EOL;
        return $this->_connection->makeQuery($sql, $this->getBindings());
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array|string  $columns
     * @return PromiseInterface<\Illuminate\Support\Collection|Exception>
     */
    public function get($columns = ['*'])
    {
        return new Promise(function ($resolve, $reject) use ($columns) {
            $this->onceWithColumns(Arr::wrap($columns), function () {
                return $this->processor->processSelect($this, $this->runSelect());
            })->then(function(array $data) use ($resolve){
                $resolve(collect($data));
            }, function(Exception $err) use ($reject) {
                $reject($err);
            });
        });
    }
    
    /**
     * Run the query as a "select" statement against the connection.
     *
     * @return PromiseInterface<array|Exception>
     */
    protected function runSelect()
    {
        return $this->_connection->select(
            $this->toSql(), $this->getBindings(), false, false, false, false
        );
    }

    /**
     * Execute the given callback while selecting the given columns.
     *
     * After running the callback, the columns are reset to the original value.
     *
     * @param  array  $columns
     * @param  callable  $callback
     * @return PromiseInterface<QueryResult|Exception>
     */
    protected function onceWithColumns($columns, $callback)
    {
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }

        $result = $callback();

        $this->columns = $original;

        return $result;
    }
    
    /**
     * Insert new records into the database.
     *
     * @param  array  $values
     * @return PromiseInterface<bool>
     */
    public function insert(array $values)
    {
        // Since every insert gets treated like a batch insert, we will make sure the
        // bindings are structured in a way that is convenient when building these
        // inserts statements by verifying these elements are actually an array.
        if (empty($values)) {
            return \React\Promise\resolve(true);
        }

        if (! is_array(reset($values))) {
            $values = [$values];
        }

        // Here, we will sort the insert keys for every record so that each insert is
        // in the same order for the record. We need to make sure this is the case
        // so there are not any errors or problems when inserting these records.
        else {
            foreach ($values as $key => $value) {
                ksort($value);

                $values[$key] = $value;
            }
        }

        $this->applyBeforeQueryCallbacks();

        // Finally, we will run this query against the database connection and return
        // the results. We will need to also flatten these bindings before running
        // the query so they are all in one huge, flattened array for execution.
        return $this->_connection->insert(
            $this->grammar->compileInsert($this, $values),
            $this->cleanBindings(Arr::flatten($values, 1))
        );
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param  array  $values
     * @param  string|null  $sequence
     * @return PromiseInterface<int|Exception>
     */
    public function insertGetId(array $values, $sequence = null)
    {
        $this->applyBeforeQueryCallbacks();

        $sql = $this->grammar->compileInsertGetId($this, $values, $sequence);

        $values = $this->cleanBindings($values);

        return $this->processor->processInsertGetId($this, $sql, $values, $sequence);
    }

    /**
     * Update records in the database.
     *
     * @param  array  $values
     * @return PromiseInterface<int|Exception>
     */
    public function update(array $values)
    {
        $this->applyBeforeQueryCallbacks();

        $sql = $this->grammar->compileUpdate($this, $values);

        return $this->_connection->update($sql, $this->cleanBindings(
            $this->grammar->prepareBindingsForUpdate($this->bindings, $values)
        ));
    }

    /**
     * Delete records from the database.
     *
     * @param  mixed  $id
     * @return PromiseInterface<int|Exception>
     */
    public function delete($id = null)
    {
        // If an ID is passed to the method, we will set the where clause to check the
        // ID to let developers to simply and quickly remove a single row from this
        // database without manually specifying the "where" clauses on the query.
        if (! is_null($id)) {
            $this->where($this->from.'.id', '=', $id);
        }

        $this->applyBeforeQueryCallbacks();

        return $this->_connection->delete(
            $this->grammar->compileDelete($this), $this->cleanBindings(
                $this->grammar->prepareBindingsForDelete($this->bindings)
            )
        );
    }

    /**
     * Execute the query and get the first result.
     *
     * @param  array|string  $columns
     * @return PromiseInterface<\Illuminate\Database\Eloquent\Model|object|array|static|null|Exception>
     */
    public function first($columns = ['*'])
    {
        return new \React\Promise\Promise(function ($resolve, $reject) use ($columns){
            $this->take(1)->get($columns)->then(
                function (Collection $data) use ($resolve){
                    $resolve($data->first());
                },
                function (Exception $ex) use ($reject){
                    $reject($ex);
                }
            );
        });
    }
    
    /**
     * Execute a query for a single record by ID.
     *
     * @param  int|string  $id
     * @param  array|string  $columns
     * @return PromiseInterface<mixed|static>
     */
    public function find($id, $columns = ['*'])
    {
        return $this->where('id', '=', $id)->first($columns);
    }
}