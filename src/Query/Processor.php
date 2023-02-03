<?php
namespace Basttyy\ReactphpOrm\Query;

use Basttyy\ReactphpOrm\PromiseInterface;
use Exception;
use Illuminate\Database\Query\Processors\Processor as IllProcessor;

class Processor extends IllProcessor
{
    /**
     * Process the results of a "select" query.
     *
     * @param  Builder  $query
     * @param  PromiseInterface<array>  $results
     * @return PromiseInterface<array>
     */
    public function processSelect($query, $results)
    {
        return $results;
    }

    /**
     * Process an  "insert get ID" query.
     *
     * @param  Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string|null  $sequence
     * @return PromiseInterface<int|Exception>
     */
    public function processInsertGetId($query, $sql, $values, $sequence = null)
    {
        return $query->getConnection()->insert($sql, $values, true);
    }

    /**
     * Process the results of a column listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumnListing($results)
    {
        return $results;
    }
}
