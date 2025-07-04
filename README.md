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
- [x] JSON log formatting
- [x] Log rotation (daily/weekly, max files)

## Configuration

### Environment Variables

Add these variables to your `.env` file:

```env
DB_LOGGER_ENABLED=true
DB_LOGGER_CONSOLE_OUTPUT=true
DB_LOGGER_FILE_LOGGING=true
DB_LOGGER_FILE_PATH=storage/logs/queries.log
DB_LOGGER_LOG_FORMAT=text # or json
DB_LOGGER_ROTATION_ENABLED=false
DB_LOGGER_ROTATION_PERIOD=daily # or weekly
DB_LOGGER_ROTATION_MAX_FILES=7
```

### Configuration Options

The logger can be configured using environment variables (as shown above) or by passing an array to its constructor. If you publish the configuration file (`config/db-logger.php`), you can manage these settings there.

Here are the available options:

- `enabled` (boolean): Enable or disable the logger completely. Default: `false`.
  - Env: `DB_LOGGER_ENABLED`
- `console_output` (boolean): Enable or disable console output. Default: `false`.
  - Env: `DB_LOGGER_CONSOLE_OUTPUT`
- `file_logging` (boolean): Enable or disable file logging. Default: `false`.
  - Env: `DB_LOGGER_FILE_LOGGING`
- `log_file` (string): Path to the log file. Default: `storage_path('logs/database-queries.log')`.
  - Env: `DB_LOGGER_FILE_PATH`
- `log_format` (string): Log format. Possible values: `text`, `json`. Default: `text`.
  - Env: `DB_LOGGER_LOG_FORMAT`
- `log_rotation_enabled` (boolean): Enable or disable log rotation. Default: `false`.
  - Env: `DB_LOGGER_ROTATION_ENABLED`
- `log_rotation_period` (string): Log rotation period. Possible values: `daily`, `weekly`. Default: `daily`.
  - Env: `DB_LOGGER_ROTATION_PERIOD`
- `log_rotation_max_files` (integer): Maximum number of rotated log files to keep. Default: `7`.
  - Env: `DB_LOGGER_ROTATION_MAX_FILES`

### Publishing the Configuration File

If you want to customize the logger configuration, you can publish the config file to your Laravel project using the following Artisan command:

```bash
php artisan vendor:publish --provider="Elminson\\DbLogger\\DatabaseQueryLoggerServiceProvider" --tag=config
```

This will copy the configuration file to `config/db-logger.php` in your Laravel application, where you can adjust the settings as needed.

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
    'log_file' => storage_path('logs/queries.log'),
    'log_format' => 'text',   // or 'json'
    'log_rotation_enabled' => false, // or true
    'log_rotation_period' => 'daily', // or 'weekly'
    'log_rotation_max_files' => 7
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
