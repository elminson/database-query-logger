<?php

namespace Elminson\DQL;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PDO;
use PDOStatement;

class DatabaseQueryLogger
{
    private ?string $logFile = null;

    private bool $consoleOutput = false;

    private bool $enabled = false;

    private bool $fileLogging = false;

    public function __construct(array $config = [])
    {
        $this->enabled = $config['enabled'] ?? false;
        $this->consoleOutput = $config['console_output'] ?? false;
        $this->fileLogging = $config['file_logging'] ?? false;

        if ($this->fileLogging) {
            $this->logFile = $config['log_file'] ?? null;
        }
    }

    /**
     * Set the log file path for query logging.
     *
     * @return $this
     */
    public function setLogFile(?string $filePath): self
    {
        $this->logFile = $filePath;
        $this->fileLogging = $filePath !== null;

        return $this;
    }

    /**
     * Enable or disable console output for query logging.
     *
     * @return $this
     */
    public function enableConsoleOutput(bool $enable = true): self
    {
        $this->consoleOutput = $enable;

        return $this;
    }

    /**
     * Enable or disable the logger completely.
     *
     * @return $this
     */
    public function enable(bool $enable = true): self
    {
        $this->enabled = $enable;

        return $this;
    }

    /**
     * Log SQL queries for both Query Builder and raw PDO statements.
     *
     * @param  Builder|QueryBuilder|PDOStatement|string  $query
     *
     * @throws \Exception
     */
    public function logQuery($query, array $bindings = [], ?ConnectionInterface $connection = null): string
    {
        if (! $this->enabled) {
            return '';
        }

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

            throw new \Exception('The provided object is not a valid Eloquent or Query Builder instance.');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Write the query to the configured output(s).
     */
    private function writeLog(string $query): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$query}".PHP_EOL;

        if ($this->consoleOutput) {
            echo $logEntry;
        }

        if ($this->fileLogging && $this->logFile !== null) {
            $directory = dirname($this->logFile);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            file_put_contents($this->logFile, $logEntry, FILE_APPEND);
        }
    }

    /**
     * Log Laravel Query Builder (Eloquent or DB).
     */
    private function logQueryBuilder(Builder|QueryBuilder $query): string
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        $formattedQuery = $this->formatQuery($sql, $bindings);
        $this->writeLog($formattedQuery);

        return $formattedQuery;
    }

    /**
     * Log raw PDOStatement queries.
     */
    private function logPdoQuery(PDOStatement $stmt, array $bindings): string
    {
        $query = $stmt->queryString;
        $formattedQuery = $this->formatQuery($query, $bindings);
        $this->writeLog($formattedQuery);

        return $formattedQuery;
    }

    /**
     * Log raw SQL queries using PDO with bindings.
     */
    private function logRawQuery(string $sql, array $bindings, ConnectionInterface $connection): string
    {
        $formattedQuery = $this->formatQuery($sql, $bindings);
        $this->writeLog($formattedQuery);

        return $formattedQuery;
    }

    /**
     * Format a SQL query by properly binding the values.
     */
    private function formatQuery(string $sql, array $bindings): string
    {
        return vsprintf(str_replace('?', '%s', $sql), array_map(function ($binding) {
            return is_numeric($binding) ? $binding : "'{$binding}'";
        }, $bindings));
    }
}
