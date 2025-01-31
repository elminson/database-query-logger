<?php

namespace Elminson\DQL;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class DatabaseQueryLogger
{
	/**
	 * Dump the SQL query of an Eloquent or Query Builder instance.
	 *
	 * @param Builder|QueryBuilder $query
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function logQuery(Builder|QueryBuilder $query)
	: string
	{
		try {
			if (!$query instanceof Builder && !$query instanceof QueryBuilder) {
				throw new \Exception('The provided object is not a valid Eloquent or Query Builder instance.');
			}

			$sql = $query->toSql();
			$bindings = $query->getBindings();
			return vsprintf(str_replace('?', '%s', $sql), array_map(function ($binding) {
				return is_numeric($binding) ? $binding : "'{$binding}'";
			}, $bindings));

		} catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}
	}
}
