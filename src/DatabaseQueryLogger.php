<?php

namespace Elminson\DQL;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PDO;
use PDOStatement;

class DatabaseQueryLogger
{
    /**
     * Log SQL queries for both Query Builder and raw PDO statements.
     *
     * @param  Builder|QueryBuilder|PDOStatement|string  $query
     *
     * @throws \Exception
     */
    public function logQuery($query, array $bindings = [], ?ConnectionInterface $connection = null): string
    {
        try {
            if ($query instanceof Builder || $query instanceof QueryBuilder) {
                return $this->logQueryBuilder($query);
            }

            if ($query instanceof PDOStatement) {
                return $this->logPdoQuery($query, $bindings);
            }

            if (is_string($query) && $connection instanceof ConnectionInterface) {
                return $this->logRawQuery($query, $bindings, $connection);
            }

            throw new \Exception('Invalid query type provided.');
        } catch (\Exception $e) {
            throw new \Exception('Query Logging Error: '.$e->getMessage());
        }
    }

    /**
     * Dump the SQL query of an Eloquent or Query Builder instance.
     *
     *
     * @throws \Exception
     */
    public function logQuery_b(Builder|QueryBuilder|PDOStatement $query): string
    {
        try {

            if ($query instanceof Builder || $query instanceof QueryBuilder) {
                return $this->logQueryBuilder($query, $connection);
            }

            if ($query instanceof PDOStatement) {
                return $this->logPdoQuery($query);
            }

            if (is_string($query) && $connection instanceof ConnectionInterface) {
                return $this->logRawQuery($query, $bindings, $connection);
            }

            if (! $query instanceof Builder && ! $query instanceof QueryBuilder) {
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

    /**
     * Log Laravel Query Builder (Eloquent or DB).
     */
    private function logQueryBuilder(Builder|QueryBuilder $query): string
    {

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        return 'Query Builder SQL: '.$this->formatQuery($sql, $bindings);
    }

    /**
     * Log raw PDOStatement queries.
     */
    private function logPdoQuery(PDOStatement $stmt, $boundParams): string
    {
        // Retrieve the bound parameters from the statement wrapper
        $boundParams = $stmt->getBoundParams();

        // Log the query with the bound parameter values
        $query = $stmt->queryString;

        foreach ($boundParams as $param => $value) {
            // Replace the placeholders with the actual bound values
            $query = str_replace($param, "'$value'", $query);
        }

        return $query;

        $queryString = $statement->queryString;
        $params = [];

        foreach ($statement->getBoundParams() as $param) {
            $params[$param['name']] = $param['value'];
        }

        return $this->formatQuery($queryString, $params);

    }

    private function getPdoStatementParams(PDOStatement $stmt): array
    {
        $reflection = new ReflectionClass($stmt);
        $property = $reflection->getProperty('boundParams');
        $property->setAccessible(true);
        $params = $property->getValue($stmt);

        $result = [];
        foreach ($params as $key => $param) {
            $result[$key] = $param['value'];
        }

        return $result;
    }

    /**
     * Log raw SQL queries using PDO with bindings.
     */
    private function logRawQuery(string $sql, array $bindings, ConnectionInterface $connection): string
    {
        $pdo = $connection->getPdo();
        $statement = $pdo->prepare($sql);

        foreach ($bindings as $index => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $statement->bindValue($index + 1, $value, $type);
        }

        ob_start();
        $statement->debugDumpParams();

        return 'Raw SQL Query: '.$this->formatQuery($sql, $bindings)."\nPDO Debug:\n".ob_get_clean();
    }

    /**
     * Format a SQL query by properly binding the values.
     */
    private function formatQuery(string $sql, array $bindings): string
    {
        return vsprintf(str_replace('?', '%s', $sql), array_map(fn ($b) => is_numeric($b) ? $b : "'{$b}'", $bindings));
    }
}
