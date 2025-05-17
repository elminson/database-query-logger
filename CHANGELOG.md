# Changelog

All notable changes to `db-logger` will be documented in this file.

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
