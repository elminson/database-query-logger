<?php

namespace Elminson\DbLogger;

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

    private string $logFormat = 'text'; // Default to text

    private bool $logRotationEnabled = false;

    private string $logRotationPeriod = 'daily';

    private int $logRotationMaxFiles = 7;

    public function __construct(array $config = [])
    {
        $this->enabled = $config['enabled'] ?? false;
        $this->consoleOutput = $config['console_output'] ?? false;
        $this->fileLogging = $config['file_logging'] ?? false;
        $this->logFormat = $config['log_format'] ?? 'text';

        $this->logRotationEnabled = $config['log_rotation_enabled'] ?? false;
        $this->logRotationPeriod = $config['log_rotation_period'] ?? 'daily';
        $this->logRotationMaxFiles = $config['log_rotation_max_files'] ?? 7;

        if ($this->fileLogging) {
            $this->logFile = $config['log_file'] ?? null;
            $this->ensureLogFileExists();
        }
    }

    /**
     * Ensure the log file and directory exist.
     */
    private function ensureLogFileExists(): void
    {
        if ($this->logFile === null) {
            return;
        }

        $directory = dirname($this->logFile);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (! file_exists($this->logFile)) {
            touch($this->logFile);
            chmod($this->logFile, 0666);
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
     * @return string The formatted SQL query
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

        if ($this->logFormat === 'json') {
            $logEntry = json_encode(['timestamp' => $timestamp, 'query' => $query]).PHP_EOL;
        } else {
            $logEntry = "[{$timestamp}] {$query}".PHP_EOL;
        }

        if ($this->consoleOutput && PHP_SAPI === 'cli') {
            echo $logEntry;
        }

        if ($this->fileLogging && $this->logFile !== null) {
            $this->ensureLogFileExists(); // Ensure base log file and directory exist

            if ($this->logRotationEnabled) {
                $this->rotateLogFile();
            }

            file_put_contents($this->logFile, $logEntry, FILE_APPEND);
        }
    }

    /**
     * Rotate the log file if necessary.
     */
    private function rotateLogFile(): void
    {
        if (! $this->logFile || ! file_exists($this->logFile)) {
            return;
        }

        $fileInfo = pathinfo($this->logFile);
        $currentFileDateSuffix = '';

        if ($this->logRotationPeriod === 'daily') {
            $currentFileDateSuffix = date('Y-m-d', filemtime($this->logFile));
            $expectedSuffix = date('Y-m-d');
        } elseif ($this->logRotationPeriod === 'weekly') {
            $currentFileDateSuffix = date('Y-W', filemtime($this->logFile));
            $expectedSuffix = date('Y-W');
        } else {
            // Unsupported period, do not rotate by date
            return;
        }

        // Rotate if the date suffix of the file is different from the expected current suffix
        if ($currentFileDateSuffix !== $expectedSuffix) {
            $rotatedFilename = $fileInfo['dirname'].'/'.$fileInfo['filename'].'-'.$currentFileDateSuffix.'.'.$fileInfo['extension'];

            // Ensure the target directory for rotated file exists
            $rotatedFileDir = dirname($rotatedFilename);
            if (! is_dir($rotatedFileDir)) {
                mkdir($rotatedFileDir, 0755, true);
            }

            if (rename($this->logFile, $rotatedFilename)) {
                touch($this->logFile); // Create new empty log file
                chmod($this->logFile, 0666);
            }

            // Manage max files
            $logFiles = glob($fileInfo['dirname'].'/'.$fileInfo['filename'].'-*.'.$fileInfo['extension']);
            if (count($logFiles) > $this->logRotationMaxFiles) {
                usort($logFiles, function ($a, $b) {
                    return filemtime($a) - filemtime($b);
                });
                $filesToDelete = count($logFiles) - $this->logRotationMaxFiles;
                for ($i = 0; $i < $filesToDelete; $i++) {
                    if (file_exists($logFiles[$i])) {
                        unlink($logFiles[$i]);
                    }
                }
            }
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
        $query = $stmt->queryString ?? '';
        if (empty($query)) {
            $this->writeLog('No query string available for this PDOStatement');

            return 'No query string available for this PDOStatement';
        }

        // If we have a PDOStatementWrapper, use its bound parameters
        if ($stmt instanceof PDOStatementWrapper) {
            $bindings = $stmt->getBoundParams();
        }

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
        // If the SQL is the fallback message, just return it
        if ($sql === 'No query string available for this PDOStatement') {
            return $sql;
        }
        // Debug: print SQL and bindings before replacement
        file_put_contents('php://stderr', "FORMATQUERY BEFORE: sql=[$sql], bindings=".var_export($bindings, true)."\n");

        // If there are named parameters (e.g., :email), replace them
        if (preg_match('/:[a-zA-Z_][a-zA-Z0-9_]*/', $sql)) {
            foreach ($bindings as $key => $value) {
                $replace = is_numeric($value) ? $value : (is_bool($value) ? ($value ? '1' : '0') : (is_null($value) ? 'NULL' : "'{$value}'"));
                // Ensure the key starts with a colon
                $param = (str_starts_with($key, ':')) ? $key : (':'.$key);
                $sql = str_replace($param, $replace, $sql);
            }
            // Normalize whitespace
            $sql = preg_replace('/\s+/', ' ', $sql);
            $sql = trim($sql);
            // Debug: print SQL after replacement
            file_put_contents('php://stderr', "FORMATQUERY AFTER: sql=[$sql]\n");

            return $sql;
        }

        // Otherwise, treat as positional
        $formattedBindings = array_map(function ($binding) {
            if (is_numeric($binding)) {
                return $binding;
            }
            if (is_bool($binding)) {
                return $binding ? '1' : '0';
            }
            if (is_null($binding)) {
                return 'NULL';
            }

            return "'{$binding}'";
        }, $bindings);

        $sql = vsprintf(str_replace('?', '%s', $sql), $formattedBindings);
        // Normalize whitespace
        $sql = preg_replace('/\s+/', ' ', $sql);
        $sql = trim($sql);
        // Debug: print SQL after replacement
        file_put_contents('php://stderr', "FORMATQUERY AFTER: sql=[$sql]\n");

        return $sql;
    }
}
