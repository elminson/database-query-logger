# Changelog

All notable changes to `db-logger` will be documented in this file.

## 1.0.1 - 2025-05-17

### Release Notes v1.0.0

#### New Features

- Added comprehensive SQL query logging support for Laravel applications
- Implemented support for both console and file-based logging
- Added PDO statement query logging with bound parameters support
- Integrated Pest PHP for enhanced testing capabilities

#### Configuration

- Added environment variables support:
  ```env
  DB_LOGGER_ENABLED=true
  DB_LOGGER_CONSOLE_OUTPUT=true
  DB_LOGGER_FILE_LOGGING=true
  DB_LOGGER_FILE_PATH=storage/logs/queries.log
  
  ```
- Added service provider for easy integration
- Implemented flexible configuration options

#### Usage Examples

##### Basic Query Logging

```php
$logger = new DatabaseQueryLogger([
    'enabled' => true,
    'console_output' => true,
    'file_logging' => true,
    'log_file' => storage_path('logs/queries.log')
]);

$query = DB::table('users')->where('email', 'example@example.com');
$logger->logQuery($query);

```
##### PDO Statement Logging

```php
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->bindParam(':email', 'example@example.com');
$logger->logQuery($stmt, ['example@example.com']);

```
##### Configuration Methods

```php
$logger->enable(true);
$logger->enableConsoleOutput(true);
$logger->setLogFile(storage_path('logs/queries.log'));

```
#### Enhancements

- Added code coverage support for tests
- Improved query formatting for better readability
- Enhanced error handling and parameter binding
- Added support for different parameter types (string, integer, boolean, null)

#### Technical Updates

- Added Pest PHP plugin support in composer.json
- Updated dependencies to latest stable versions
- Added test coverage configuration
- Improved code organization and structure

#### Testing

- Added comprehensive test suite for query logging
- Implemented tests for different logging scenarios:
  - Console output logging
  - File-based logging
  - PDO statement logging
  - Parameter binding tests
  - Different data type handling tests
  

#### Dependencies

- Updated to PHP 8.1+ requirement
- Added Laravel 10.x support
- Integrated Pest PHP for testing
- Added Laravel Pint for code formatting

#### Bug Fixes

- Fixed styling issues
- Resolved PDOStatementWrapper integration
- Fixed parameter binding issues
- Addressed composer plugin configuration

#### Documentation

- Added inline code documentation
- Improved method documentation
- Added usage examples in tests
- Updated README with comprehensive usage instructions
- Added environment configuration guide

This release focuses on providing a robust and flexible database query logging solution for Laravel applications, with emphasis on code quality, testing, and maintainability. The package now includes comprehensive documentation and examples for easy integration into any Laravel project.

## 0.0.7 - 2025-05-17

### Release Notes v1.0.0

#### New Features

- Added comprehensive SQL query logging support for Laravel applications
- Implemented support for both console and file-based logging
- Added PDO statement query logging with bound parameters support
- Integrated Pest PHP for enhanced testing capabilities

#### Enhancements

- Added code coverage support for tests
- Improved query formatting for better readability
- Enhanced error handling and parameter binding
- Added support for different parameter types (string, integer, boolean, null)

#### Technical Updates

- Added Pest PHP plugin support in composer.json
- Updated dependencies to latest stable versions
- Added test coverage configuration
- Improved code organization and structure

#### Testing

- Added comprehensive test suite for query logging
- Implemented tests for different logging scenarios:
  - Console output logging
  - File-based logging
  - PDO statement logging
  - Parameter binding tests
  - Different data type handling tests
  

#### Dependencies

- Updated to PHP 8.1+ requirement
- Added Laravel 10.x support
- Integrated Pest PHP for testing
- Added Laravel Pint for code formatting

#### Configuration

- Added flexible configuration options for:
  - Console output
  - File logging
  - Log file path
  - Enable/disable logging
  

#### Bug Fixes

- Fixed styling issues
- Resolved PDOStatementWrapper integration
- Fixed parameter binding issues
- Addressed composer plugin configuration

#### Documentation

- Added inline code documentation
- Improved method documentation
- Added usage examples in tests

This release focuses on providing a robust and flexible database query logging solution for Laravel applications, with emphasis on code quality, testing, and maintainability.
