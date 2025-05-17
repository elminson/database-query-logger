<?php

use Elminson\DQL\DatabaseQueryLogger;

if (! function_exists('log_query')) {
    /**
     * Dump the SQL query of an Eloquent builder instance.
     *
     * @param mixed $query
     * @param bool $print
     * @param bool $return
     * @param array $bindings
     * @return string|void
     *
     * @throws Exception
     */
    function log_query(mixed $query, bool $print = false, bool $return = false, array $bindings = [])
    {
        $logger = new DatabaseQueryLogger;
        $logger->enable(true);
        $sql = $logger->logQuery($query, $bindings);

        if ($print) {
            echo $sql;
        }

        if ($return) {
            return $sql;
        }
    }
}
