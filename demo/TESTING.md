# Demo Application Testing

This directory contains comprehensive test scripts to verify the demo application setup and functionality.

## Test Scripts

### 1. `verify-setup.sh` - Basic Setup Verification
Quick verification script that checks the demo application structure without requiring dependencies.

```bash
./verify-setup.sh
```

This script verifies:
- File structure completeness
- Laravel application structure
- Configuration files
- Application files
- Database files
- View files
- Test scripts
- Composer configuration

### 2. `run-tests.sh` - Main Test Runner
The primary test runner that executes all available test suites.

```bash
./run-tests.sh
```

This script runs:
- Main setup tests
- Comprehensive PHP tests  
- QR flow specific tests
- Laravel framework tests (if available)

### 3. `test-demo.sh` - Setup Verification Tests
Shell script that tests the basic application setup and configuration.

```bash
./test-demo.sh
```

Tests include:
- Composer dependencies verification
- Environment configuration
- Database migration and seeding
- Application startup
- Basic QR code functionality
- Development server startup

### 4. `test-setup.php` - Comprehensive PHP Tests
Detailed PHP test script that thoroughly tests all application components.

```bash
php test-setup.php
```

Tests include:
- Composer install verification
- Database migration and seeding
- Application startup and service registration
- QR code generation and login flow
- Token management and validation

### 5. `test-qr-flow.php` - QR Code Flow Tests
Specialized test script focusing on the QR code generation and login flow functionality.

```bash
php test-qr-flow.php
```

Tests include:
- Token generation and uniqueness
- QR code generation with various parameters
- Token validation and retrieval
- Complete login flow simulation
- Token expiration handling
- Error handling scenarios

## Running Tests

### Quick Start
To verify basic setup (no dependencies required):
```bash
./verify-setup.sh
```

To run all tests at once (requires dependencies):
```bash
./run-tests.sh
```

### Individual Test Suites
To run specific test suites:

```bash
# Basic setup verification (no dependencies)
./verify-setup.sh

# Basic setup tests (requires dependencies)
./test-demo.sh

# Comprehensive PHP tests
php test-setup.php

# QR flow specific tests
php test-qr-flow.php
```

## Test Requirements

Before running tests, ensure:

1. **Composer Dependencies**: Run `composer install` in the demo directory
2. **Environment Configuration**: Copy `.env.example` to `.env` or let the tests create it
3. **Database**: SQLite database will be created automatically
4. **Permissions**: Test scripts have executable permissions

## Expected Results

All tests should pass for a properly configured demo application. The tests verify:

- ✅ All required dependencies are installed
- ✅ Environment is properly configured
- ✅ Database migrations run successfully
- ✅ Test data is seeded correctly
- ✅ Application can start without errors
- ✅ QR code generation works
- ✅ Token management functions correctly
- ✅ Login flow completes successfully
- ✅ Error handling works as expected

## Troubleshooting

### Common Issues

**Composer Dependencies Missing**
```bash
cd demo
composer install
```

**Database Issues**
```bash
# Recreate database
rm -f database/database.sqlite
php artisan migrate:fresh --seed
```

**Permission Issues**
```bash
chmod +x test-demo.sh run-tests.sh
```

**Environment Issues**
```bash
# Regenerate environment
cp .env.example .env
php artisan key:generate
```

### Test Failures

If tests fail:

1. Check the error messages for specific issues
2. Ensure all dependencies are installed
3. Verify environment configuration
4. Check database connectivity
5. Review Laravel logs in `storage/logs/`

## Integration with CI/CD

These test scripts can be integrated into CI/CD pipelines:

```yaml
# Example GitHub Actions step
- name: Run Demo Tests
  run: |
    cd demo
    composer install
    ./run-tests.sh
```

## Adding New Tests

To add new tests:

1. **Shell Tests**: Add to `test-demo.sh` using the `run_test` function
2. **PHP Tests**: Add methods to `test-setup.php` or `test-qr-flow.php`
3. **New Test Files**: Create new test files and add them to `run-tests.sh`

## Test Coverage

The test scripts cover:

- **Setup Verification**: 100% of setup requirements
- **Core Functionality**: All major application features
- **Package Integration**: Complete scan login functionality
- **Error Scenarios**: Common failure cases
- **Performance**: Basic performance validation

For more detailed testing, consider adding:
- Unit tests with PHPUnit/Pest
- Browser tests with Laravel Dusk
- API tests with Laravel's HTTP testing
- Load testing for performance validation