<?php

namespace Elminson\DQL\Tests;

use Elminson\DQL\DatabaseQueryLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PHPUnit\Framework\TestCase;
use Mockery;

class DatabaseQueryLoggerTest extends TestCase
{
    private DatabaseQueryLogger $logger;
    private string $testLogFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new DatabaseQueryLogger();
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

    public function testLoggerIsDisabledByDefault()
    {
        $query = $this->createMockQuery();
        $result = $this->logger->logQuery($query);
        $this->assertEquals('', $result);
    }

    public function testLoggerCanBeEnabled()
    {
        $this->logger->enable(true);
        $query = $this->createMockQuery();
        $result = $this->logger->logQuery($query);
        $this->assertNotEmpty($result);
    }

    public function testLoggerCanBeConfiguredViaConstructor()
    {
        $logger = new DatabaseQueryLogger([
            'enabled' => true,
            'console_output' => true,
            'file_logging' => true,
            'log_file' => $this->testLogFile
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

    public function testConsoleOutputCanBeEnabled()
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

    public function testFileLogging()
    {
        $this->logger->enable(true);
        $this->logger->setLogFile($this->testLogFile);
        
        $query = $this->createMockQuery();
        $this->logger->logQuery($query);
        
        $this->assertFileExists($this->testLogFile);
        $logContents = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('SELECT * FROM users', $logContents);
    }

    public function testLogFileDirectoryCreation()
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

    public function testQueryFormatting()
    {
        $this->logger->enable(true);
        
        $query = $this->createMockQueryWithBindings();
        $result = $this->logger->logQuery($query);
        
        $this->assertStringContainsString("SELECT * FROM users WHERE email = 'test@example.com'", $result);
    }

    public function testInvalidQueryBuilderThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The provided object is not a valid Eloquent or Query Builder instance.');
        $this->logger->enable(true);
        $this->logger->logQuery(new \stdClass());
    }

    public function testExceptionInQueryProcessingIsRethrown()
    {
        $this->logger->enable(true);
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('toSql')->andThrow(new \Exception('Test exception'));
        $query->shouldReceive('getBindings')->andReturn([]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');
        $this->logger->logQuery($query);
    }

    public function testLogEntryFormat()
    {
        $this->logger->enable(true);
        $this->logger->setLogFile($this->testLogFile);
        
        $query = $this->createMockQuery();
        $this->logger->logQuery($query);
        
        $logContents = file_get_contents($this->testLogFile);
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] SELECT \* FROM users/', $logContents);
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