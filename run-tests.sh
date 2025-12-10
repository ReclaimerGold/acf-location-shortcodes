#!/bin/bash
#
# ACF Service Management Suite - Test Runner Script
#
# Convenience script for running tests with common options.
# Usage: ./run-tests.sh [option]
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Print header
echo -e "${BLUE}╔════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  ACF Service Management Suite - Test Runner   ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════╝${NC}"
echo ""

# Check if composer is available
if ! command -v composer &> /dev/null; then
    echo -e "${RED}✗ Composer is not installed${NC}"
    echo ""
    echo "Please install Composer first:"
    echo "  https://getcomposer.org/download/"
    echo ""
    echo "Or install via package manager:"
    echo "  Ubuntu/Debian: sudo apt install composer"
    echo "  Fedora:        sudo dnf install composer"
    echo "  macOS:         brew install composer"
    echo ""
    exit 1
fi

# Check if composer dependencies are installed
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}⚠ Composer dependencies not found. Installing...${NC}"
    composer install
    echo ""
fi

# Function to run tests
run_tests() {
    local cmd=$1
    local description=$2
    
    echo -e "${BLUE}► Running: ${description}${NC}"
    echo ""
    
    if eval "$cmd"; then
        echo ""
        echo -e "${GREEN}✓ Tests passed!${NC}"
        return 0
    else
        echo ""
        echo -e "${RED}✗ Tests failed!${NC}"
        return 1
    fi
}

# Parse command line arguments
case "${1:-all}" in
    all)
        run_tests "./vendor/bin/phpunit" "All tests"
        ;;
    
    unit)
        run_tests "./vendor/bin/phpunit --testsuite unit" "Unit tests only"
        ;;
    
    integration)
        run_tests "./vendor/bin/phpunit --testsuite integration" "Integration tests only"
        ;;
    
    coverage)
        echo -e "${BLUE}► Generating coverage report...${NC}"
        ./vendor/bin/phpunit --coverage-html coverage
        echo ""
        echo -e "${GREEN}✓ Coverage report generated: coverage/index.html${NC}"
        
        # Try to open in browser (works on macOS and most Linux)
        if command -v xdg-open > /dev/null; then
            xdg-open coverage/index.html
        elif command -v open > /dev/null; then
            open coverage/index.html
        else
            echo -e "${YELLOW}Open coverage/index.html in your browser${NC}"
        fi
        ;;
    
    syntax)
        echo -e "${BLUE}► Checking PHP syntax...${NC}"
        echo ""
        
        find includes/ -name "*.php" -print0 | while IFS= read -r -d '' file; do
            if php -l "$file" > /dev/null 2>&1; then
                echo -e "${GREEN}✓${NC} $file"
            else
                echo -e "${RED}✗${NC} $file"
                php -l "$file"
            fi
        done
        
        echo ""
        echo -e "${GREEN}✓ Syntax check complete${NC}"
        ;;
    
    watch)
        echo -e "${BLUE}► Starting test watcher...${NC}"
        echo -e "${YELLOW}Press Ctrl+C to stop${NC}"
        echo ""
        
        # Simple file watcher using inotifywait (Linux) or fswatch (macOS)
        if command -v inotifywait > /dev/null; then
            while true; do
                inotifywait -r -e modify includes/ tests/ 2>/dev/null
                clear
                ./vendor/bin/phpunit
            done
        elif command -v fswatch > /dev/null; then
            fswatch -o includes/ tests/ | while read; do
                clear
                ./vendor/bin/phpunit
            done
        else
            echo -e "${RED}Error: File watcher not available${NC}"
            echo "Install inotifywait (Linux) or fswatch (macOS)"
            exit 1
        fi
        ;;
    
    quick)
        run_tests "./vendor/bin/phpunit --no-coverage" "Quick tests (no coverage)"
        ;;
    
    verbose)
        run_tests "./vendor/bin/phpunit --verbose" "Verbose test output"
        ;;
    
    filter)
        if [ -z "$2" ]; then
            echo -e "${RED}Error: Please provide a filter pattern${NC}"
            echo "Usage: ./run-tests.sh filter <pattern>"
            exit 1
        fi
        run_tests "./vendor/bin/phpunit --filter '$2'" "Tests matching: $2"
        ;;
    
    file)
        if [ -z "$2" ]; then
            echo -e "${RED}Error: Please provide a test file${NC}"
            echo "Usage: ./run-tests.sh file <path>"
            exit 1
        fi
        run_tests "./vendor/bin/phpunit '$2'" "Test file: $2"
        ;;
    
    install)
        if ! command -v composer &> /dev/null; then
            echo -e "${RED}✗ Composer is not installed${NC}"
            echo "Please install Composer: https://getcomposer.org/download/"
            exit 1
        fi
        
        echo -e "${BLUE}► Installing test dependencies...${NC}"
        composer install
        echo ""
        echo -e "${GREEN}✓ Test dependencies installed${NC}"
        ;;
    
    update)
        if ! command -v composer &> /dev/null; then
            echo -e "${RED}✗ Composer is not installed${NC}"
            echo "Please install Composer: https://getcomposer.org/download/"
            exit 1
        fi
        
        echo -e "${BLUE}► Updating test dependencies...${NC}"
        composer update
        echo ""
        echo -e "${GREEN}✓ Test dependencies updated${NC}"
        ;;
    
    clean)
        echo -e "${BLUE}► Cleaning test artifacts...${NC}"
        rm -rf coverage/
        rm -f .phpunit.result.cache
        echo -e "${GREEN}✓ Test artifacts cleaned${NC}"
        ;;
    
    help|--help|-h)
        echo "Usage: ./run-tests.sh [option]"
        echo ""
        echo "Options:"
        echo "  all          Run all tests (default)"
        echo "  unit         Run unit tests only"
        echo "  integration  Run integration tests only"
        echo "  coverage     Generate HTML coverage report"
        echo "  syntax       Check PHP syntax for all files"
        echo "  watch        Watch for file changes and run tests"
        echo "  quick        Run tests without coverage (faster)"
        echo "  verbose      Run tests with verbose output"
        echo "  filter <pat> Run tests matching pattern"
        echo "  file <path>  Run specific test file"
        echo "  install      Install test dependencies"
        echo "  update       Update test dependencies"
        echo "  clean        Remove test artifacts"
        echo "  help         Show this help message"
        echo ""
        echo "Examples:"
        echo "  ./run-tests.sh                    # Run all tests"
        echo "  ./run-tests.sh unit               # Unit tests only"
        echo "  ./run-tests.sh coverage           # Generate coverage"
        echo "  ./run-tests.sh filter parse       # Tests with 'parse' in name"
        echo "  ./run-tests.sh file tests/Unit/ACFHelpersTest.php"
        ;;
    
    *)
        echo -e "${RED}Error: Unknown option '$1'${NC}"
        echo "Use './run-tests.sh help' for usage information"
        exit 1
        ;;
esac

echo ""
