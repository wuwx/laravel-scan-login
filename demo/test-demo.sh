#!/bin/bash

# Demo Application Test Script
# This script tests the complete demo application setup and functionality

set -e  # Exit on any error

echo "🚀 Demo Application Test Suite"
echo "============================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test counters
TESTS_PASSED=0
TESTS_FAILED=0

# Function to print test results
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}❌ $2${NC}"
        ((TESTS_FAILED++))
    fi
}

# Function to run a test and capture result
run_test() {
    local test_name="$1"
    local test_command="$2"
    
    echo -e "${BLUE}🔍 Testing: $test_name${NC}"
    
    if eval "$test_command" > /dev/null 2>&1; then
        print_result 0 "$test_name passed"
    else
        print_result 1 "$test_name failed"
        echo -e "${YELLOW}   Command: $test_command${NC}"
    fi
    echo ""
}

# Change to demo directory
cd "$(dirname "$0")"

echo "📍 Working directory: $(pwd)"
echo ""

# Test 1: Composer Install Verification
echo -e "${BLUE}📦 Testing Composer Install...${NC}"
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}   Vendor directory not found. Please run 'composer install' first.${NC}"
    echo -e "${YELLOW}   Skipping composer-dependent tests...${NC}"
    echo ""
    echo "To install dependencies:"
    echo "  composer install"
    echo ""
    exit 1
fi

run_test "Composer dependencies" "[ -d vendor ] && [ -f composer.lock ]"
run_test "Laravel framework installed" "[ -f vendor/laravel/framework/composer.json ]"
run_test "Scan login package installed" "[ -d vendor/wuwx/laravel-scan-login ] || [ -d ../vendor/wuwx/laravel-scan-login ]"

# Test 2: Environment Setup
echo -e "${BLUE}🔧 Testing Environment Setup...${NC}"
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        echo -e "${YELLOW}   Creating .env from .env.example...${NC}"
        cp .env.example .env
    else
        echo -e "${YELLOW}   Creating basic .env file...${NC}"
        cat > .env << EOF
APP_NAME="Laravel Scan Login Demo"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

SCAN_LOGIN_TOKEN_EXPIRE_MINUTES=5
SCAN_LOGIN_QR_SIZE=200
EOF
    fi
fi

run_test "Environment file exists" "[ -f .env ]"

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo -e "${YELLOW}   Generating application key...${NC}"
    php artisan key:generate --no-interaction
fi

run_test "Application key generated" "grep -q 'APP_KEY=base64:' .env"

# Test 3: Database Migration and Seeding
echo -e "${BLUE}🗄️  Testing Database Migration and Seeding...${NC}"

# Create database directory if it doesn't exist
mkdir -p database

# Create SQLite database file if it doesn't exist
if [ ! -f "database/database.sqlite" ]; then
    echo -e "${YELLOW}   Creating SQLite database...${NC}"
    touch database/database.sqlite
fi

run_test "Database file exists" "[ -f database/database.sqlite ]"

# Run migrations
echo -e "${YELLOW}   Running database migrations...${NC}"
php artisan migrate:fresh --force --no-interaction

run_test "Database migrations" "php artisan migrate:status --no-interaction"

# Run seeders
echo -e "${YELLOW}   Running database seeders...${NC}"
php artisan db:seed --force --no-interaction

run_test "Database seeding" "php artisan tinker --execute='echo App\\Models\\User::count();' | grep -q '[1-9]'"

# Test 4: Application Startup
echo -e "${BLUE}🚀 Testing Application Startup...${NC}"

# Test if artisan commands work
run_test "Artisan commands" "php artisan --version"
run_test "Route listing" "php artisan route:list --no-interaction"
run_test "Config caching" "php artisan config:cache --no-interaction"

# Test 5: QR Code Generation and Login Flow
echo -e "${BLUE}📱 Testing QR Code Generation and Login Flow...${NC}"

# Run the PHP test script
if [ -f "test-setup.php" ]; then
    echo -e "${YELLOW}   Running comprehensive PHP tests...${NC}"
    if php test-setup.php; then
        print_result 0 "PHP test suite"
    else
        print_result 1 "PHP test suite"
    fi
else
    echo -e "${YELLOW}   PHP test script not found, running basic tests...${NC}"
    
    # Basic QR code test using artisan tinker
    run_test "QR code generation" "php artisan tinker --execute='
        \$generator = app(Wuwx\\LaravelScanLogin\\Services\\QrCodeGenerator::class);
        \$token = str_random(32);
        \$qr = \$generator->generate(\$token);
        echo !empty(\$qr) ? \"success\" : \"failed\";
    ' | grep -q 'success'"
fi

# Test 6: Web Server Test (optional)
echo -e "${BLUE}🌐 Testing Web Server (optional)...${NC}"

# Check if we can start the development server briefly
echo -e "${YELLOW}   Testing development server startup...${NC}"
timeout 5s php artisan serve --port=8001 > /dev/null 2>&1 &
SERVER_PID=$!
sleep 2

if kill -0 $SERVER_PID 2>/dev/null; then
    print_result 0 "Development server startup"
    kill $SERVER_PID 2>/dev/null || true
else
    print_result 1 "Development server startup"
fi

# Final Results
echo ""
echo "📊 Test Results Summary"
echo "======================"
echo ""

TOTAL_TESTS=$((TESTS_PASSED + TESTS_FAILED))

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 All tests passed! ($TESTS_PASSED/$TOTAL_TESTS)${NC}"
    echo ""
    echo -e "${GREEN}✅ The demo application is ready to use!${NC}"
    echo ""
    echo "To start the demo application:"
    echo "  cd demo"
    echo "  php artisan serve"
    echo ""
    echo "Then visit: http://localhost:8000"
    exit 0
else
    echo -e "${RED}⚠️  Some tests failed. ($TESTS_PASSED/$TOTAL_TESTS passed)${NC}"
    echo ""
    echo "Please check the errors above and ensure:"
    echo "1. All dependencies are installed (composer install)"
    echo "2. Environment is properly configured (.env file)"
    echo "3. Database is set up correctly"
    echo "4. All required services are available"
    exit 1
fi