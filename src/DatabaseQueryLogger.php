<?php

namespace Elminson\DQL;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

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
     * @param string|null $filePath
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
     * @param bool $enable
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
     * @param bool $enable
     * @return $this
     */
    public function enable(bool $enable = true): self
    {
        $this->enabled = $enable;
        return $this;
    }

    /**
     * Log the SQL query of an Eloquent or Query Builder instance.
     *
     * @param mixed $query
     * @return string The formatted SQL query
     * @throws \Exception
     */
    public function logQuery(mixed $query): string
    {
        if (!$this->enabled) {
            return '';
        }

        try {
            if (!($query instanceof Builder || $query instanceof QueryBuilder)) {
                throw new \Exception('The provided object is not a valid Eloquent or Query Builder instance.');
            }

            $sql = $query->toSql();
            $bindings = $query->getBindings();
            $formattedQuery = vsprintf(str_replace('?', '%s', $sql), array_map(function ($binding) {
                return is_numeric($binding) ? $binding : "'{$binding}'";
            }, $bindings));

            $this->writeLog($formattedQuery);

            return $formattedQuery;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Write the query to the configured output(s).
     *
     * @param string $query
     * @return void
     */
    private function writeLog(string $query): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$query}" . PHP_EOL;

        if ($this->consoleOutput) {
            echo $logEntry;
        }

        if ($this->fileLogging && $this->logFile !== null) {
            $directory = dirname($this->logFile);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            file_put_contents($this->logFile, $logEntry, FILE_APPEND);
        }
    }
}
