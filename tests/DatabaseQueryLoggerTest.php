<?php

namespace Elminson\DbLogger\Tests;

use Elminson\DbLogger\DatabaseQueryLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseQueryLoggerTest extends TestCase
{
    private DatabaseQueryLogger $logger;

    private string $testLogFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new DatabaseQueryLogger;
        $this->testLogFile = sys_get_temp_dir() . '/test-database-queries.log';

        // Clean up any existing test log file
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test log file
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
        Mockery::close();
        parent::tearDown();
    }

    public function test_logger_is_disabled_by_default()
    {
        $query = $this->createMockQuery();
        $result = $this->logger->logQuery($query);
        $this->assertEquals('', $result);
    }

    public function test_logger_can_be_enabled()
    {
        $this->logger->enable(true);
        $query = $this->createMockQuery();
        $result = $this->logger->logQuery($query);
        $this->assertNotEmpty($result);
    }

    public function test_logger_can_be_configured_via_constructor()
    {
        $logger = new DatabaseQueryLogger([
            'enabled' => true,
            'console_output' => true,
            'file_logging' => true,
            'log_file' => $this->testLogFile,
        ]);

        $query = $this->createMockQuery();

        // Capture console output
        ob_start();
        $result = $logger->logQuery($query);
        $output = ob_get_clean();

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('SELECT * FROM users', $output);
        $this->assertFileExists($this->testLogFile);
        $this->assertStringContainsString('SELECT * FROM users', file_get_contents($this->testLogFile));
    }

    public function test_console_output_can_be_enabled()
    {
        $this->logger->enable(true);
        $this->logger->enableConsoleOutput(true);

        $query = $this->createMockQuery();

        // Capture console output
        ob_start();
        $this->logger->logQuery($query);
        $output = ob_get_clean();

        $this->assertStringContainsString('SELECT * FROM users', $output);
    }

    public function test_file_logging()
    {
        $this->logger->enable(true);
        $this->logger->setLogFile($this->testLogFile);

        $query = $this->createMockQuery();
        $this->logger->logQuery($query);

        $this->assertFileExists($this->testLogFile);
        $logContents = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('SELECT * FROM users', $logContents);
    }

    public function test_log_file_directory_creation()
    {
        $nestedLogFile = sys_get_temp_dir() . '/nested/dir/test-database-queries.log';
        $this->logger->enable(true);
        $this->logger->setLogFile($nestedLogFile);

        $query = $this->createMockQuery();
        $this->logger->logQuery($query);

        $this->assertFileExists($nestedLogFile);
        $this->assertDirectoryExists(dirname($nestedLogFile));

        // Cleanup
        unlink($nestedLogFile);
        rmdir(dirname($nestedLogFile));
    }

    public function test_query_formatting()
    {
        $this->logger->enable(true);

        $query = $this->createMockQueryWithBindings();
        $result = $this->logger->logQuery($query);

        $this->assertStringContainsString("SELECT * FROM users WHERE email = 'test@example.com'", $result);
    }

    public function test_invalid_query_builder_throws_exception()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The provided object is not a valid Eloquent or Query Builder instance.');
        $this->logger->enable(true);
        $this->logger->logQuery(new \stdClass);
    }

    public function test_exception_in_query_processing_is_rethrown()
    {
        $this->logger->enable(true);
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('toSql')->andThrow(new \Exception('Test exception'));
        $query->shouldReceive('getBindings')->andReturn([]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');
        $this->logger->logQuery($query);
    }

    public function test_log_entry_format()
    {
        $this->logger->enable(true);
        $this->logger->setLogFile($this->testLogFile);

        $query = $this->createMockQuery();
        $this->logger->logQuery($query);

        $logContents = file_get_contents($this->testLogFile);
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] SELECT \* FROM users/', $logContents);
    }

    public function test_json_log_format()
    {
        $logger = new  DatabaseQueryLogger([ // Changed namespace
            'enabled' => true,
            'file_logging' => true,
            'log_file' => $this->testLogFile,
            'log_format' => 'json',
        ]);

        $query = $this->createMockQuery();
        $logger->logQuery($query);

        $this->assertFileExists($this->testLogFile);
        $logContents = file_get_contents($this->testLogFile);

        // Check if the log entry is a valid JSON string
        $jsonEntry = json_decode(trim($logContents));
        $this->assertNotNull($jsonEntry);
        $this->assertObjectHasProperty('timestamp', $jsonEntry);
        $this->assertObjectHasProperty('query', $jsonEntry);
        $this->assertEquals('SELECT * FROM users', $jsonEntry->query);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $jsonEntry->timestamp);
    }

    public function test_log_rotation_daily()
    {
        $baseLogFileName = 'test-rotation.log';
        $this->testLogFile = sys_get_temp_dir() . '/' . $baseLogFileName;
        $fileInfo = pathinfo($this->testLogFile);
        $logDir = $fileInfo['dirname'];

        // Clean up any existing test log files from previous runs
        $existingFiles = glob($logDir . '/' . $fileInfo['filename'] . '*');
        foreach ($existingFiles as $file) {
            unlink($file);
        }

        $logger = new  DatabaseQueryLogger([
            'enabled' => true,
            'file_logging' => true,
            'log_file' => $this->testLogFile,
            'log_rotation_enabled' => true,
            'log_rotation_period' => 'daily',
            'log_rotation_max_files' => 2, // Keep it small for testing
        ]);

        $query = $this->createMockQuery();

        // Simulate logging on day 1
        touch($this->testLogFile, strtotime('2023-01-01 10:00:00'));
        $logger->logQuery($query); // Log 1
        $this->assertFileExists($this->testLogFile);
        $this->assertStringContainsString('SELECT * FROM users', file_get_contents($this->testLogFile));

        // Simulate logging on day 2
        // Important: Modify the current log file's mtime to simulate it being from "yesterday" before the next log call
        // The rotation logic checks the *existing* log file's mtime to decide if it needs to be rotated.
        touch($this->testLogFile, strtotime('2023-01-01 23:59:59')); // Original log is now from 2023-01-01
        // For the *new* log entry, the logger will use current time (simulated as 2023-01-02 for this part of test)
        // We achieve this by "freezing" time for the date() call within rotateLogFile logic by changing mtime of ACTUAL log file
        // The check is `date('Y-m-d', filemtime($this->logFile))` vs `date('Y-m-d')` [current time]
        // To make rotateLogFile think it's a new day, the current time must be > filemtime
        // So, we don't need to mock PHP's date() if we ensure filemtime is old enough.

        // To simulate the script running on 2023-01-02 for the *next* log:
        // We need to make the original log file appear as if it was last modified on 2023-01-01.
        // The next call to logQuery will then see that date('Y-m-d') [simulated by test execution time] is 2023-01-02 (or later)
        // and rotate the file.

        // Manually change the modification time of the current log file to "yesterday"
        // The `rotateLogFile` method compares `date('Y-m-d', filemtime($this->logFile))` with `date('Y-m-d')` (current time).
        // By setting filemtime to yesterday, the next log write (which happens "today") will trigger rotation.
        $yesterday = strtotime('-1 day');
        touch($this->testLogFile, $yesterday);
        $logger->logQuery($query); // Log 2 (triggers rotation of Log 1)

        $rotatedFile1Name = $logDir . '/' . $fileInfo['filename'] . '-' . date('Y-m-d', $yesterday) . '.' . $fileInfo['extension'];
        $this->assertFileExists($rotatedFile1Name, "Rotated file for day 1 should exist: $rotatedFile1Name");
        $this->assertFileExists($this->testLogFile, "New current log file for day 2 should exist");
        $this->assertStringContainsString('SELECT * FROM users', file_get_contents($this->testLogFile)); // Log 2 content
        $this->assertStringContainsString('SELECT * FROM users', file_get_contents($rotatedFile1Name)); // Log 1 content

        // Simulate logging on day 3
        $dayBeforeYesterday = strtotime('-2 days');
        touch($this->testLogFile, $yesterday); // Current log file is from "yesterday" (relative to "today")
        $logger->logQuery($query); // Log 3 (triggers rotation of Log 2)

        $rotatedFile2Name = $logDir . '/' . $fileInfo['filename'] . '-' . date('Y-m-d', $yesterday) . '.' . $fileInfo['extension'];
        // This assertion is tricky due to filemtime of $rotatedFile1Name vs $rotatedFile2Name if tests run near midnight
        // Let's ensure $rotatedFile2Name is what we expect from the previous $this->testLogFile
        $this->assertFileExists($rotatedFile2Name, "Rotated file for day 2 should exist: $rotatedFile2Name");
        $this->assertFileExists($this->testLogFile, "New current log file for day 3 should exist");

        // Simulate logging on day 4 (this should delete the oldest log file: day 1's log)
        // Make current log file seem like it's from yesterday again
        touch($this->testLogFile, $yesterday);
        $logger->logQuery($query); // Log 4 (triggers rotation of Log 3)

        $rotatedFile3Name = $logDir . '/' . $fileInfo['filename'] . '-' . date('Y-m-d', $yesterday) . '.' . $fileInfo['extension'];
        $this->assertFileExists($rotatedFile3Name);

        // Check max files: We expect current + 2 rotated files. The first one (day 1) should be gone.
        $actualLogFiles = glob($logDir . '/' . $fileInfo['filename'] . '-*.' . $fileInfo['extension']);
        $this->assertCount(2, $actualLogFiles, "Should only keep max_files (2) rotated logs.");

        // The file from day 1 (which had $dayBeforeYesterday's date in its name) should be deleted.
        $originalRotatedFile1Name = $logDir . '/' . $fileInfo['filename'] . '-' . date('Y-m-d', $dayBeforeYesterday) . '.' . $fileInfo['extension'];
        $this->assertFileDoesNotExist($originalRotatedFile1Name, "Oldest log file (day 1) should have been deleted.");

        // Clean up all created files
        foreach (glob($logDir . '/' . $fileInfo['filename'] . '*') as $file) {
            unlink($file);
        }
    }

    private function createMockQuery(): Builder|QueryBuilder
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('toSql')->andReturn('SELECT * FROM users');
        $query->shouldReceive('getBindings')->andReturn([]);

        return $query;
    }

    private function createMockQueryWithBindings(): Builder|QueryBuilder
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('toSql')->andReturn('SELECT * FROM users WHERE email = ?');
        $query->shouldReceive('getBindings')->andReturn(['test@example.com']);

        return $query;
    }
}
