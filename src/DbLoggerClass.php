<?php

namespace Elminson\DbLogger;

use Illuminate\Database\Eloquent\Builder;

class DbLoggerClass
{
	/**
	 * Dump the SQL query of an Eloquent builder instance.
	 *
	 * @param mixed $query
	 * @return void
	 */
	public function ddQuery($query)
	{
		if ($query instanceof Builder) {
			$sql = $query->toSql();
			$bindings = $query->getBindings();
			$fullSql = vsprintf(str_replace('?', '%s', $sql), array_map(function ($binding) {
				return is_numeric($binding) ? $binding : "'{$binding}'";
			}, $bindings));
			dd($fullSql);
		} else {
			dd('The provided object is not a valid Eloquent query builder instance.');
		}
	}
}
