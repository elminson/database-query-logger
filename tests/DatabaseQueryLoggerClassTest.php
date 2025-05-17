<?php
// @codeCoverageIgnore

use Elminson\DQL\DatabaseQueryLogger;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

beforeEach(function () {
    // Set up the database connection
    $db = new DB;
    $db->addConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);
    $db->setAsGlobal();
    $db->bootEloquent();

    // Create a test table
    DB::schema()->create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('email');
    });

    // Insert a test record
    DB::table('users')->insert([
        'email' => 'example@example.com',
    ]);
});

it('logs query with console output disabled', function () {
    $logger = new DatabaseQueryLogger([
        'enabled' => true,
        'console_output' => false
    ]);
    $query = DB::table('users')->where('email', 'example@example.com');

    ob_start();
    $result = $logger->logQuery($query);
    $output = ob_get_clean();

    expect($output)->toBe('');
    expect($result)->toContain('select * from "users" where "email" = \'example@example.com\'');
});

it('logs query with console output enabled', function () {
    $logger = new DatabaseQueryLogger([
        'enabled' => true,
        'console_output' => true
    ]);
    $query = DB::table('users')->where('email', 'example@example.com');

    ob_start();
    $result = $logger->logQuery($query);
    $output = ob_get_clean();

    expect($output)->toContain('select * from "users" where "email" = \'example@example.com\'');
    expect($result)->toContain('select * from "users" where "email" = \'example@example.com\'');
});

it('logs query with file logging', function () {
    $logFile = sys_get_temp_dir() . '/test-query.log';
    $logger = new DatabaseQueryLogger([
        'enabled' => true,
        'file_logging' => true,
        'log_file' => $logFile
    ]);
    $query = DB::table('users')->where('email', 'example@example.com');

    $result = $logger->logQuery($query);
    
    expect($result)->toContain('select * from "users" where "email" = \'example@example.com\'');
    expect(file_exists($logFile))->toBeTrue();
    expect(file_get_contents($logFile))->toContain('select * from "users" where "email" = \'example@example.com\'');

    // Cleanup
    unlink($logFile);
});

it('logs query with both console and file output', function () {
    $logFile = sys_get_temp_dir() . '/test-query.log';
    $logger = new DatabaseQueryLogger([
        'enabled' => true,
        'console_output' => true,
        'file_logging' => true,
        'log_file' => $logFile
    ]);
    $query = DB::table('users')->where('email', 'example@example.com');

    ob_start();
    $result = $logger->logQuery($query);
    $output = ob_get_clean();

    expect($output)->toContain('select * from "users" where "email" = \'example@example.com\'');
    expect($result)->toContain('select * from "users" where "email" = \'example@example.com\'');
    expect(file_exists($logFile))->toBeTrue();
    expect(file_get_contents($logFile))->toContain('select * from "users" where "email" = \'example@example.com\'');

    // Cleanup
    unlink($logFile);
});
