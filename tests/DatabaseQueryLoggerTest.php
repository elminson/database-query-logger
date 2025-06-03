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

        // Elimina logs anteriores de pruebas previas
        foreach (glob($logDir . '/' . $fileInfo['filename'] . '*') as $file) {
            unlink($file);
        }

        $logger = new DatabaseQueryLogger([
            'enabled' => true,
            'file_logging' => true,
            'log_file' => $this->testLogFile,
            'log_rotation_enabled' => true,
            'log_rotation_period' => 'daily',
            'log_rotation_max_files' => 2,
        ]);

        $query = $this->createMockQuery();

        // Day 1
        touch($this->testLogFile, strtotime('2023-01-01 10:00:00'));
        $logger->logQuery($query);
        $this->assertFileExists($this->testLogFile);
        $this->assertStringContainsString('SELECT * FROM users', file_get_contents($this->testLogFile));

        // Day 2 (force rotation by simulating that the current log file is from "yesterday")
        $yesterday = strtotime('-1 day');
        touch($this->testLogFile, $yesterday);
        $logger->logQuery($query); // Rotación ocurre aquí

        $rotatedFile1Name = $logDir . '/' . $fileInfo['filename'] . '-' . date('Y-m-d', $yesterday) . '.' . $fileInfo['extension'];
        $this->assertFileExists($rotatedFile1Name);
        $this->assertFileExists($this->testLogFile);
        $this->assertStringContainsString('SELECT * FROM users', file_get_contents($this->testLogFile));
        $this->assertStringContainsString('SELECT * FROM users', file_get_contents($rotatedFile1Name));

        // Day 3
        $dayBeforeYesterday = strtotime('-2 days');
        touch($this->testLogFile, $yesterday);
        $logger->logQuery($query);

        $rotatedFile2Name = $logDir . '/' . $fileInfo['filename'] . '-' . date('Y-m-d', $yesterday) . '.' . $fileInfo['extension'];
        $this->assertFileExists($rotatedFile2Name);
        $this->assertFileExists($this->testLogFile);

        // Day 4 (the oldest log file is deleted)
        touch($this->testLogFile, $yesterday);
        $logger->logQuery($query);

        $rotatedFile3Name = $logDir . '/' . $fileInfo['filename'] . '-' . date('Y-m-d', $yesterday) . '.' . $fileInfo['extension'];
        $this->assertFileExists($rotatedFile3Name);

        // Verify that only the 2 most recent log files are kept
        $actualLogFiles = glob($logDir . '/' . $fileInfo['filename'] . '-*.' . $fileInfo['extension']);
        $this->assertCount(2, $actualLogFiles);

        $originalRotatedFile1Name = $logDir . '/' . $fileInfo['filename'] . '-' . date('Y-m-d', $dayBeforeYesterday) . '.' . $fileInfo['extension'];
        $this->assertFileDoesNotExist($originalRotatedFile1Name);

        // Final cleanup
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
