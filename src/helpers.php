<?php

use Elminson\DbLogger\DbLoggerClass;

if (!function_exists('ddQuery')) {
	/**
	 * Dump the SQL query of an Eloquent builder instance.
	 *
	 * @param mixed $query
	 * @return void
	 */
	function ddQuery($query)
	{
		$logger = new DbLoggerClass();
		$logger->ddQuery($query);
	}
}
