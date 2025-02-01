<?php

use Elminson\DQL\DatabaseQueryLogger;
use Elminson\DQL\PDOStatementWrapper;
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
    // Create a PDO connection with SQLite in-memory database
    $this->pdo = new PDO('sqlite::memory:');
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $this->pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, [PDOStatementWrapper::class]);

    // Create test table
    $this->pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT, active BOOLEAN, score REAL)');

    // Create logger instance
    $this->logger = new DatabaseQueryLogger;
});

it('dumps the SQL query with print = false and return = false', function () {
    $logger = new DatabaseQueryLogger;
    $query = DB::table('users')->where('email', 'example@example.com');

    // Capture the output of ddQuery
    $output = '';
    try {
        $output = $logger->logQuery($query);
    } catch (Exception $e) {
        dd($e->getMessage());
    }

    expect($output)->toContain('select * from "users" where "email" = \'example@example.com\'');
});

it('tests log_query with print = false and return = false', function () {
    $query = DB::table('users')->where('email', 'example@example.com');

    // Capture the output of log_query
    ob_start();
    try {
        log_query($query, []);
    } catch (Exception $e) {
        dd($e->getMessage());
    }
    $output = ob_get_clean();
    expect($output)->toBe('');
});

it('tests log_query with print = true and return = false', function () {
    $query = DB::table('users')->where('email', 'example@example.com');

    // Capture the output of log_query
    ob_start();
    try {
        log_query($query, [], true);
    } catch (Exception $e) {
        dd($e->getMessage());
    }
    $output = ob_get_clean();
    expect($output)->toContain('select * from "users" where "email" = \'example@example.com\'');
});

it('tests log_query with print = false and return = true', function () {
    $query = DB::table('users')->where('email', 'example@example.com');

    // Capture the output of log_query
    $result = '';
    try {
        $result = log_query($query, [], false, true);
    } catch (Exception $e) {
        dd($e->getMessage());
    }
    expect($result)->toBe('Query Builder SQL: select * from "users" where "email" = \'example@example.com\'');
});

it('tests log_query with print = true and return = true', function () {
    $query = DB::table('users')->where('email', 'example@example.com');

    // Capture the output of log_query
    ob_start();
    $result = '';
    try {
        $result = log_query($query, [], true, true);
    } catch (Exception $e) {
        dd($e->getMessage());
    }
    $output = ob_get_clean();
    expect($output)->toContain('select * from "users" where "email" = \'example@example.com\'');
    expect($result)->toBe('Query Builder SQL: select * from "users" where "email" = \'example@example.com\'');
});

test('logs PDO statement query with bound parameters', function () {
    // Prepare the statement
    $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');

    // Bind parameter
    $email = 'example@example.com';
    $stmt->bindParam(':email', $email);

    // Get logged query
    $output = $this->logger->logQuery($stmt, [':email' => $email]);

    // Assert the output contains the properly formatted query
    expect($output)
        ->toBeString()
        ->toContain("SELECT * FROM users WHERE email = 'example@example.com'");
});

test('handles multiple bound parameters in PDO statement', function () {
    // Prepare statement with multiple parameters
    $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email AND id = :id');

    // Bind multiple parameters
    $email = 'test@example.com';
    $id = 1;
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    // Get logged query
    $output = $this->logger->logQuery($stmt, [':email' => $email, ':id' => $id]);

    // Assert the output contains all bound parameters
    expect($output)
        ->toBeString()
        ->toContain("SELECT * FROM users WHERE email = 'test@example.com' AND id = '1'");
});

// test('throws exception for invalid PDO statement', function () {
// 	// Create an invalid statement (table doesn't exist)
// 	$stmt = $this->pdo->prepare('SELECT * FROM nonexistent_table WHERE id = :id');
// 	$id = 1;
// 	$stmt->bindParam(':id', $id);
//
// 	// Expect a PDOException to be thrown
// 	$this->expectException(PDOException::class);
// 	$this->expectExceptionMessage('no such table: nonexistent_table');
//
// 	// Execute the statement
// 	$stmt->execute();
// });

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

    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':active', $active, PDO::PARAM_BOOL);
    $stmt->bindParam(':score', $score, PDO::PARAM_STR);

    // Get logged query
    $params = [
        ':id' => $id,
        ':email' => $email,
        ':active' => $active,
        ':score' => $score,
    ];
    $output = $this->logger->logQuery($stmt, $params);

    // Assert the output contains all bound parameters
    expect($output)
        ->toBeString()
        ->toContain("id = '123'")
        ->toContain("email = 'test@example.com'")
        ->toContain("active = '1'")
        ->toContain("score = '95.5'");
});
