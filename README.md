# Database Query Logger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elminson/db-logger.svg?style=flat-square)](https://packagist.org/packages/elminson/db-logger)
[![Tests](https://img.shields.io/github/actions/workflow/status/elminson/db-logger/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/elminson/db-logger/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/elminson/db-logger.svg?style=flat-square)](https://packagist.org/packages/elminson/db-logger)

A simple PHP package to log SQL queries from Eloquent or Query Builder instances.

## Installation

You can install the package via Composer:

```bash
composer require elminson/database-query-logger
```
This is where your description should go. Try and limit it to a paragraph or two. Consider adding a small example.

## Features

- [x] Log SQL queries from Eloquent or Query Builder instances
- [ ] Log SQL queries to a file or the console
- [ ] Log SQL queries with or without bindings
- [ ] Log SQL queries with or without execution time
- [ ] Log SQL queries with or without the backtrace
- [ ] Log SQL queries with or without the caller
- [ ] Log SQL queries with or without the memory usage
- [ ] Log SQL queries with or without the memory peak usage

## Usage

### Logging a Query

To log a query, you can use the log_query helper function:

```php
use Illuminate\Database\Capsule\Manager as DB;

// Set up the database connection
$db = new DB;
$db->addConnection([
    'driver' => 'sqlite',
    'database' => ':memory:',
]);
$db->setAsGlobal();
$db->bootEloquent();

// Create a test table
DB::schema()->create('users', function ($table) {
    $table->increments('id');
    $table->string('email');
});

// Insert a test record
DB::table('users')->insert([
    'email' => 'example@example.com',
]);

$query = DB::table('users')->where('email', 'example@example.com');

// Log the query
log_query($query, true, true);

```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Elminson De Oleo Baez](https://github.com/elminson)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
