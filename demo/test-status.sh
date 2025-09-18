#!/bin/bash

# Demo Application Test Status
# This script shows the status of all test scripts and what they verify

echo "🧪 Demo Application Test Status"
echo "==============================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Change to demo directory
cd "$(dirname "$0")"

echo "📍 Working directory: $(pwd)"
echo ""

# Check test script availability
echo -e "${BLUE}📋 Available Test Scripts:${NC}"
echo ""

if [ -f "verify-setup.sh" ] && [ -x "verify-setup.sh" ]; then
    echo -e "${GREEN}✅ verify-setup.sh${NC} - Basic setup verification (no dependencies required)"
else
    echo -e "${RED}❌ verify-setup.sh${NC} - Missing or not executable"
fi

if [ -f "test-demo.sh" ] && [ -x "test-demo.sh" ]; then
    echo -e "${GREEN}✅ test-demo.sh${NC} - Setup verification tests (requires dependencies)"
else
    echo -e "${RED}❌ test-demo.sh${NC} - Missing or not executable"
fi

if [ -f "test-setup.php" ]; then
    echo -e "${GREEN}✅ test-setup.php${NC} - Comprehensive PHP tests"
else
    echo -e "${RED}❌ test-setup.php${NC} - Missing"
fi

if [ -f "test-qr-flow.php" ]; then
    echo -e "${GREEN}✅ test-qr-flow.php${NC} - QR code flow tests"
else
    echo -e "${RED}❌ test-qr-flow.php${NC} - Missing"
fi

if [ -f "run-tests.sh" ] && [ -x "run-tests.sh" ]; then
    echo -e "${GREEN}✅ run-tests.sh${NC} - Main test runner"
else
    echo -e "${RED}❌ run-tests.sh${NC} - Missing or not executable"
fi

echo ""

# Check dependencies
echo -e "${BLUE}📦 Dependency Status:${NC}"
echo ""

if [ -d "vendor" ]; then
    echo -e "${GREEN}✅ Composer dependencies installed${NC}"
    DEPS_INSTALLED=true
else
    echo -e "${YELLOW}⚠️  Composer dependencies not installed${NC}"
    echo "   Run: composer install"
    DEPS_INSTALLED=false
fi

if [ -f ".env" ]; then
    echo -e "${GREEN}✅ Environment file exists${NC}"
else
    echo -e "${YELLOW}⚠️  Environment file missing${NC}"
    echo "   Run: cp .env.example .env && php artisan key:generate"
fi

if [ -f "database/database.sqlite" ]; then
    echo -e "${GREEN}✅ Database file exists${NC}"
else
    echo -e "${YELLOW}⚠️  Database file missing${NC}"
    echo "   Will be created automatically during tests"
fi

echo ""

# Recommended test sequence
echo -e "${BLUE}🚀 Recommended Test Sequence:${NC}"
echo ""

echo "1. ${GREEN}Basic Setup Verification${NC} (no dependencies required):"
echo "   ./verify-setup.sh"
echo ""

if [ "$DEPS_INSTALLED" = true ]; then
    echo "2. ${GREEN}Install Dependencies${NC} (already done):"
    echo "   ✅ Dependencies are installed"
    echo ""
    
    echo "3. ${GREEN}Run All Tests${NC}:"
    echo "   ./run-tests.sh"
    echo ""
    
    echo "4. ${GREEN}Individual Test Suites${NC} (optional):"
    echo "   ./test-demo.sh          # Basic setup tests"
    echo "   php test-setup.php      # Comprehensive tests"
    echo "   php test-qr-flow.php    # QR flow tests"
else
    echo "2. ${YELLOW}Install Dependencies${NC} (required):"
    echo "   composer install"
    echo ""
    
    echo "3. ${YELLOW}Run All Tests${NC} (after installing dependencies):"
    echo "   ./run-tests.sh"
    echo ""
    
    echo "4. ${YELLOW}Individual Test Suites${NC} (after installing dependencies):"
    echo "   ./test-demo.sh          # Basic setup tests"
    echo "   php test-setup.php      # Comprehensive tests"
    echo "   php test-qr-flow.php    # QR flow tests"
fi

echo ""

# Test coverage summary
echo -e "${BLUE}📊 Test Coverage Summary:${NC}"
echo ""

echo "✅ File Structure Verification"
echo "✅ Laravel Application Structure"
echo "✅ Configuration Files"
echo "✅ Database Migration and Seeding"
echo "✅ Application Startup"
echo "✅ QR Code Generation"
echo "✅ Token Management"
echo "✅ Login Flow Simulation"
echo "✅ Error Handling"
echo "✅ Token Expiration"

echo ""

if [ "$DEPS_INSTALLED" = true ]; then
    echo -e "${GREEN}🎉 Ready to run tests! Start with: ./run-tests.sh${NC}"
else
    echo -e "${YELLOW}⚠️  Install dependencies first: composer install${NC}"
fi