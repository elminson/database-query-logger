<?php

use Elminson\DQL\DatabaseQueryLogger;

if (!function_exists('log_query')) {
	/**
	 * Dump the SQL query of an Eloquent builder instance.
	 *
	 * @param mixed $query
	 * @param bool  $print
	 * @param bool  $return
	 *
	 * @return string|void
	 * @throws Exception
	 */
	function log_query(mixed $query, bool $print = false, bool $return = false)
	{

		$logger = new DatabaseQueryLogger();
		$sql = $logger->logQuery($query);

		if ($print) {
			echo $sql;
		}

		if ($return) {
			return $sql;
		}

	}
}
