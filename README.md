# Database Query Logger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elminson/db-logger.svg?style=flat-square)](https://packagist.org/packages/elminson/db-logger)
[![Tests](https://img.shields.io/github/actions/workflow/status/elminson/db-logger/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/elminson/db-logger/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/elminson/db-logger.svg?style=flat-square)](https://packagist.org/packages/elminson/db-logger)

A powerful PHP package for logging SQL queries from Laravel applications, supporting both Eloquent and Query Builder instances, with flexible output options.

## Installation

You can install the package via Composer:

```bash
composer require elminson/database-query-logger
```

## Features

- [x] Log SQL queries from Eloquent or Query Builder instances
- [x] Log SQL queries to a file or the console
- [x] Log SQL queries with parameter bindings
- [x] Support for PDO statements
- [x] Configurable logging options
- [x] Timestamp-based log entries
- [x] Automatic directory creation for log files
- [x] Support for different parameter types (string, integer, boolean, null)

## Configuration

### Environment Variables

Add these variables to your `.env` file:

```env
DB_LOGGER_ENABLED=true
DB_LOGGER_CONSOLE_OUTPUT=true
DB_LOGGER_FILE_LOGGING=true
DB_LOGGER_FILE_PATH=storage/logs/queries.log
```

### Service Provider

Register the service provider in `config/app.php`:

```php
'providers' => [
    // ...
    Elminson\DQL\DatabaseQueryLoggerServiceProvider::class,
],
```

## Usage

### Basic Usage

```php
use Elminson\DQL\DatabaseQueryLogger;
use Illuminate\Database\Capsule\Manager as DB;

// Initialize the logger
$logger = new DatabaseQueryLogger([
    'enabled' => true,
    'console_output' => true,
    'file_logging' => true,
    'log_file' => storage_path('logs/queries.log')
]);

// Log a query
$query = DB::table('users')->where('email', 'example@example.com');
$logger->logQuery($query);
```

### PDO Statement Logging

```php
use PDO;
use PDOStatement;

$pdo = new PDO('sqlite::memory:');
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->bindParam(':email', 'example@example.com');

$logger->logQuery($stmt, ['example@example.com']);
```

### Raw Query Logging

```php
$sql = 'SELECT * FROM users WHERE email = ?';
$bindings = ['example@example.com'];
$logger->logQuery($sql, $bindings, $connection);
```

### Configuration Methods

```php
// Enable/disable logging
$logger->enable(true);

// Enable/disable console output
$logger->enableConsoleOutput(true);

// Set log file path
$logger->setLogFile(storage_path('logs/queries.log'));
```

## Testing

Run the tests with:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Elminson De Oleo Baez](https://github.com/elminson)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
