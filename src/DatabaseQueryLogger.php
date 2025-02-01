<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use ReflectionProperty;

class DatabaseQueryLogger
{
    public function logEloquent(Model $model, string $query, array $bindings): string
    {
        return 'Eloquent Query: ' . $this->formatQuery($query, $bindings);
    }

    public function logPdo(string $sql, array $bindings): string
    {
        return 'PDO Query: ' . $this->formatQuery($sql, $bindings);
    }

    public function logRaw(string $sql, array $bindings): string
    {
        return 'Raw SQL Query: ' . $this->formatQuery($sql, $bindings);
    }

    private function formatQuery(string $sql, array $bindings): string
    {
        return vsprintf(str_replace('?', '%s', $sql), array_map(function ($binding) {
            return is_numeric($binding) ? $binding : "'{$binding}'";
        }, $bindings));
    }

    public function logEloquentParameters(Model $model)
    {
        try {
            $reflection = new ReflectionClass($model);
            $parameters = [];

            foreach ($reflection->getProperties() as $property) {
                $type = $property->getType();
                if (! $type || !$type->isCollection()) {
                    continue;
                }

                $parameters[$property->getName()] = $type->getRawType();
            }

            return $parameters;
        } catch (\ReflectionException $e) {
            return [];
        }
    }

    public function getPdoStatementParams(PDOStatement $stmt)
    {
        try {
            $queryString = $stmt->queryString;
            $params = [];

            foreach ($stmt->getParameters() as $key => $value) {
                $type = $value->getType();
                $params[$key] = [
                    'name' => $key,
                    'value' => is_null($value) ? 'NULL' : $value,
                    'type' => $type ? $type->getName() : 'string'
                ];
            }

            return $params;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function logQueryParameters(string $query, array $parameters)
    {
        return "Query Parameters:\n" . implode("\n", [
            'Query: ' . $query,
            ...array_map(function ($parameter) use ($parameters) {
                return sprintf('- %s: %s', $parameter['name'], $parameter['value']);
            }, $parameters)
        ]);
    }
}
