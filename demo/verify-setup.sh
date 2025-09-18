#!/bin/bash

# Demo Application Setup Verification
# This script verifies the basic setup without requiring dependencies

# set -e  # Don't exit on errors, we want to continue testing

echo "🔍 Demo Application Setup Verification"
echo "======================================"
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

# Test 1: Basic File Structure
echo -e "${BLUE}📁 Testing File Structure...${NC}"
run_test "Composer configuration" "[ -f composer.json ]"
run_test "Artisan command" "[ -f artisan ]"
run_test "Application bootstrap" "[ -f bootstrap/app.php ]"
run_test "Environment example" "[ -f .env.example ]"

# Test 2: Laravel Application Structure
echo -e "${BLUE}🏗️  Testing Laravel Structure...${NC}"
run_test "App directory" "[ -d app ]"
run_test "Config directory" "[ -d config ]"
run_test "Database directory" "[ -d database ]"
run_test "Public directory" "[ -d public ]"
run_test "Resources directory" "[ -d resources ]"
run_test "Routes directory" "[ -d routes ]"
run_test "Storage directory" "[ -d storage ]"

# Test 3: Configuration Files
echo -e "${BLUE}⚙️  Testing Configuration Files...${NC}"
run_test "App configuration" "[ -f config/app.php ]"
run_test "Database configuration" "[ -f config/database.php ]"
run_test "Auth configuration" "[ -f config/auth.php ]"
run_test "Scan login configuration" "[ -f config/scan-login.php ]"

# Test 4: Application Files
echo -e "${BLUE}📄 Testing Application Files...${NC}"
run_test "User model" "[ -f app/Models/User.php ]"
run_test "Demo controller" "[ -f app/Http/Controllers/DemoController.php ]"
run_test "Login controller" "[ -f app/Http/Controllers/Auth/LoginController.php ]"
run_test "HTTP Kernel" "[ -f app/Http/Kernel.php ]"

# Test 5: Database Files
echo -e "${BLUE}🗄️  Testing Database Files...${NC}"
run_test "Users migration" "[ -f database/migrations/2024_01_01_000001_create_users_table.php ]"
run_test "Password reset migration" "[ -f database/migrations/2024_01_01_000002_create_password_reset_tokens_table.php ]"
run_test "Sessions migration" "[ -f database/migrations/2024_01_01_000003_create_sessions_table.php ]"
run_test "Demo seeder" "[ -f database/seeders/DemoSeeder.php ]"
run_test "Database seeder" "[ -f database/seeders/DatabaseSeeder.php ]"

# Test 6: View Files
echo -e "${BLUE}👁️  Testing View Files...${NC}"
run_test "Demo index view" "[ -f resources/views/demo/index.blade.php ]"
run_test "Demo dashboard view" "[ -f resources/views/demo/dashboard.blade.php ]"
run_test "Mobile login view" "[ -f resources/views/demo/mobile-login.blade.php ]"
run_test "Login view" "[ -f resources/views/demo/auth/login.blade.php ]"

# Test 7: Test Scripts
echo -e "${BLUE}🧪 Testing Test Scripts...${NC}"
run_test "Main test script" "[ -f test-demo.sh ] && [ -x test-demo.sh ]"
run_test "PHP test script" "[ -f test-setup.php ]"
run_test "QR flow test script" "[ -f test-qr-flow.php ]"
run_test "Test runner script" "[ -f run-tests.sh ] && [ -x run-tests.sh ]"

# Test 8: Composer Configuration
echo -e "${BLUE}📦 Testing Composer Configuration...${NC}"
if [ -f composer.json ]; then
    run_test "Laravel framework dependency" "grep -q 'laravel/framework' composer.json"
    run_test "Scan login dependency" "grep -q 'laravel-scan-login' composer.json"
    run_test "Local repository configuration" "grep -q 'repositories' composer.json"
    run_test "Dev minimum stability" "grep -q 'minimum-stability.*dev' composer.json"
fi

# Final Results
echo ""
echo "📊 Setup Verification Results"
echo "============================="
echo ""

TOTAL_TESTS=$((TESTS_PASSED + TESTS_FAILED))

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 All setup verification tests passed! ($TESTS_PASSED/$TOTAL_TESTS)${NC}"
    echo ""
    echo -e "${GREEN}✅ The demo application structure is complete!${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Install dependencies: composer install"
    echo "2. Run full tests: ./run-tests.sh"
    echo "3. Start the application: php artisan serve"
    exit 0
else
    echo -e "${RED}⚠️  Some setup verification tests failed. ($TESTS_PASSED/$TOTAL_TESTS passed)${NC}"
    echo ""
    echo "Please ensure all required files are in place before proceeding."
    exit 1
fi