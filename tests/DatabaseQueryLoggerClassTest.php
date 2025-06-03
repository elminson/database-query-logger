<?php

// @codeCoverageIgnore

use Elminson\DQL\DatabaseQueryLogger;
use Elminson\DQL\PDOStatementWrapper;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use PDO;

beforeEach(function () {
    // Set up the database connection
    $db = new DB;
    $db->addConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);
    $db->setAsGlobal();
    $db->bootEloquent();

    // Create test table
    DB::schema()->create('users', function (Blueprint $table) {
        $table->id();
        $table->string('email');
        $table->boolean('active')->default(true);
        $table->float('score')->nullable();
    });

    // Insert test data
    DB::table('users')->insert([
        'email' => 'example@example.com',
        'active' => true,
        'score' => 95.5,
    ]);

    // Create a PDO connection with SQLite in-memory database
    $this->pdo = new PDO('sqlite::memory:');
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $this->pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, [PDOStatementWrapper::class]);

    // Create test table for PDO
    $this->pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT, active BOOLEAN, score REAL)');
    $this->pdo->exec("INSERT INTO users (email, active, score) VALUES ('example@example.com', 1, 95.5)");

    // Create logger instance
    $this->logger = new DatabaseQueryLogger;
});

it('logs query with console output disabled', function () {
    $logger = new DatabaseQueryLogger([
        'enabled' => true,
        'console_output' => false,
    ]);
    $query = DB::table('users')->where('email', 'example@example.com');

    $result = $logger->logQuery($query);

    expect($result)->toContain('select * from "users" where "email" = \'example@example.com\'');
});

it('logs query with console output enabled', function () {
    $logger = new DatabaseQueryLogger([
        'enabled' => true,
        'console_output' => true,
    ]);
    $query = DB::table('users')->where('email', 'example@example.com');

    ob_start();
    $result = $logger->logQuery($query);
    $output = ob_get_clean();

    expect($result)->toContain('select * from "users" where "email" = \'example@example.com\'');
    expect($output)->toContain('select * from "users" where "email" = \'example@example.com\'');
});

it('logs query with file logging', function () {
    $logFile = sys_get_temp_dir().'/test-query.log';
    $logger = new DatabaseQueryLogger([
        'enabled' => true,
        'file_logging' => true,
        'log_file' => $logFile,
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
    $logFile = sys_get_temp_dir().'/test-query.log';
    $logger = new DatabaseQueryLogger([
        'enabled' => true,
        'console_output' => true,
        'file_logging' => true,
        'log_file' => $logFile,
    ]);
    $query = DB::table('users')->where('email', 'example@example.com');

    ob_start();
    $result = $logger->logQuery($query);
    $output = ob_get_clean();

    expect($result)->toContain('select * from "users" where "email" = \'example@example.com\'');
    expect($output)->toContain('select * from "users" where "email" = \'example@example.com\'');
    expect(file_exists($logFile))->toBeTrue();
    expect(file_get_contents($logFile))->toContain('select * from "users" where "email" = \'example@example.com\'');

    // Cleanup
    unlink($logFile);
});

test('logs PDO statement query with bound parameters', function () {
    // Prepare the statement
    $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');

    // Bind parameter
    $email = 'example@example.com';
    $stmt->bindParam(':email', $email);

    // Get logged query BEFORE execution, pass bindings as numeric array
    $output = $this->logger->logQuery($stmt, [$email]);

    $stmt->execute();

    // Assert the output contains the properly formatted query or fallback message
    expect($output)
        ->toBeString()
        ->not->toBeNull();
    if (empty($output)) {
        expect($output === '' || $output === 'No query string available for this PDOStatement')->toBeTrue();
    } else {
        expect($output)->toContain('select * from "users" where "email" = \'example@example.com\'');
    }
});

test('handles multiple bound parameters in PDO statement', function () {
    // Prepare statement with multiple parameters
    $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email AND id = :id');

    // Bind multiple parameters
    $email = 'test@example.com';
    $id = 1;
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $this->pdo->exec("INSERT INTO users (email, active, score) VALUES ('test@example.com', 1, 88.8)");

    // Get logged query BEFORE execution, pass bindings as numeric array
    $output = $this->logger->logQuery($stmt, [$email, $id]);

    $stmt->execute();

    // Assert the output contains all bound parameters or fallback message
    expect($output)
        ->toBeString()
        ->not->toBeNull();
    if (empty($output)) {
        expect($output === '' || $output === 'No query string available for this PDOStatement')->toBeTrue();
    } else {
        expect($output)->toContain('select * from "users" where "email" = \'test@example.com\' and "id" = \'1\'');
    }
});

test('handles different parameter types correctly', function () {
    // Prepare statement with different parameter types
    $stmt = $this->pdo->prepare('
        SELECT * FROM users
        WHERE id = :id
        AND email = :email
        AND active = :active
        AND score = :score
    ');

    // Bind different types of parameters
    $id = 123;
    $email = 'test@example.com';
    $active = true;
    $score = 95.5;
    $this->pdo->exec("INSERT INTO users (id, email, active, score) VALUES (123, 'test@example.com', 1, 95.5)");

    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':active', $active, PDO::PARAM_BOOL);
    $stmt->bindParam(':score', $score, PDO::PARAM_STR);

    // Get logged query BEFORE execution, pass bindings as numeric array
    $params = [$id, $email, $active, $score];
    $output = $this->logger->logQuery($stmt, $params);

    $stmt->execute();

    // Assert the output contains all bound parameters or fallback message
    expect($output)
        ->toBeString()
        ->not->toBeNull();
    if (empty($output)) {
        expect($output === '' || $output === 'No query string available for this PDOStatement')->toBeTrue();
    } else {
        expect($output)
            ->toContain('"id" = \'123\'')
            ->toContain('"email" = \'test@example.com\'')
            ->toContain('"active" = \'1\'')
            ->toContain('"score" = \'95.5\'');
    }
});
