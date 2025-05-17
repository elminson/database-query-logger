<?php

namespace Elminson\DQL\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_log_query_with_print_enabled()
    {
        $query = $this->createMockQuery();

        // Capture console output
        ob_start();
        log_query($query, true);
        $output = ob_get_clean();

        $this->assertStringContainsString('SELECT * FROM users', $output);
    }

    public function test_log_query_with_return_enabled()
    {
        $query = $this->createMockQuery();
        $result = log_query($query, false, true);

        $this->assertStringContainsString('SELECT * FROM users', $result);
    }

    public function test_log_query_with_both_print_and_return_enabled()
    {
        $query = $this->createMockQuery();

        // Capture console output
        ob_start();
        $result = log_query($query, true, true);
        $output = ob_get_clean();

        $this->assertStringContainsString('SELECT * FROM users', $output);
        $this->assertStringContainsString('SELECT * FROM users', $result);
    }

    public function test_log_query_with_query_bindings()
    {
        $query = $this->createMockQueryWithBindings();
        $result = log_query($query, false, true);

        $this->assertStringContainsString("SELECT * FROM users WHERE email = 'test@example.com'", $result);
    }

    public function test_log_query_with_invalid_query()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The provided object is not a valid Eloquent or Query Builder instance.');
        log_query(new \stdClass);
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
