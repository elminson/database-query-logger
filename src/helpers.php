<?php

use Elminson\DQL\DatabaseQueryLogger;

if (! function_exists('log_query')) {
    /**
     * Dump the SQL query of an Eloquent builder instance.
     *
     *
     * @return string|void
     *
     * @throws Exception
     */
    function log_query(mixed $query, $bindings = [], bool $print = false, bool $return = false)
    {

        $logger = new DatabaseQueryLogger;
        $sql = $logger->logQuery($query, $bindings);

        if ($print) {
            echo $sql;
        }

        if ($return) {
            return $sql;
        }

    }
}
