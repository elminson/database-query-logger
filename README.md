# Database Query Logger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elminson/database-query-logger.svg?style=flat-square)](https://packagist.org/packages/elminson/database-query-logger)
[![Tests](https://img.shields.io/github/actions/workflow/status/elminson/db-logger/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/elminson/db-logger/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/elminson/database-query-logger.svg?style=flat-square)](https://packagist.org/packages/elminson/database-query-logger)

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

### Manual Configuration

Copy the configuration file to your Laravel project:

```bash
cp vendor/elminson/database-query-logger/src/config/database-logger.php config/db-logger.php
```

### Service Provider

Register the service provider in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\DatabaseQueryLoggerServiceProvider::class,
],
```

## Usage

### Basic Usage

```php
use Elminson\DbLogger\DatabaseQueryLogger;
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

## Log All Queries in Laravel

To log every SQL query executed by your Laravel application, add the following to your `app/Providers/AppServiceProvider.php`:

```php
use Illuminate\Support\Facades\DB;
use Elminson\DbLogger\DatabaseQueryLogger;

public function boot()
{
    $logger = new DatabaseQueryLogger(config('db-logger'));

    DB::listen(function ($query) use ($logger) {
        $logger->logQuery(
            $query->sql,
            $query->bindings,
            $query->connection
        );
    });
}
```

Make sure your `config/db-logger.php` and `.env` are set up as described above. This will ensure all queries are logged according to your configuration.
