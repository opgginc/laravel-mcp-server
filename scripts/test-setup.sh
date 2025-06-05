#!/bin/bash

# Laravel MCP Server Test Setup Script
# This script creates a fresh Laravel project and configures it to test the MCP server package
# Usage: ./scripts/test-setup.sh [test-directory-name]

set -e  # Exit on any error

# Configuration
TEST_DIR="${1:-laravel-mcp-test}"
PACKAGE_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
print_step() {
    printf "${BLUE}==>${NC} ${1}\n"
}

print_success() {
    printf "${GREEN}✓${NC} ${1}\n"
}

print_warning() {
    printf "${YELLOW}⚠${NC} ${1}\n"
}

print_error() {
    printf "${RED}✗${NC} ${1}\n"
}

check_command() {
    if ! command -v "$1" &> /dev/null; then
        print_error "$1 is required but not installed."
        exit 1
    fi
}

# Check prerequisites
print_step "Checking prerequisites..."
check_command "composer"
check_command "php"
check_command "npm"

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION_ID;")
if [ "$PHP_VERSION" -lt 80200 ]; then
    print_error "PHP 8.2 or higher is required. Current version: $(php -v | head -n1)"
    exit 1
fi
print_success "PHP version check passed"

# Step 1: Clean up existing directory and create new one
print_step "Cleaning up existing test directory: $TEST_DIR"
if [ -d "$TEST_DIR" ]; then
    print_warning "Directory $TEST_DIR already exists. Removing it..."
    rm -rf "$TEST_DIR"
    print_success "Existing directory removed"
fi

print_step "Creating test directory: $TEST_DIR"
mkdir "$TEST_DIR"
cd "$TEST_DIR"
print_success "Test directory created"

# Step 2: Create blank Laravel project
print_step "Creating blank Laravel project..."
composer create-project laravel/laravel . --no-interaction --prefer-dist
print_success "Laravel project created"

# Step 3: Configure local package repository
print_step "Configuring local package repository..."
composer config repositories.mcp-server "{\"type\": \"path\", \"url\": \"$PACKAGE_PATH\"}"
print_success "Package repository configured"

# Step 4: Install the MCP server package
print_step "Installing laravel-mcp-server package..."
composer require opgginc/laravel-mcp-server:@dev --no-interaction
print_success "MCP server package installed"

# Step 5: Publish configuration
print_step "Publishing MCP server configuration..."
php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider" --no-interaction
print_success "Configuration published"

# Step 6: Configure Streamable HTTP provider
print_step "Configuring Streamable HTTP provider..."
cat > config/mcp-server.php << 'EOF'
<?php

return [
    'enabled' => true,
    'server' => [
        'name' => 'Test MCP Server',
        'version' => '1.0.0',
    ],
    'server_provider' => 'streamable_http',
    'default_path' => 'mcp',
    'middlewares' => [],
    'tools' => [
        \OPGG\LaravelMcpServer\Services\ToolService\Examples\HelloWorldTool::class,
        \OPGG\LaravelMcpServer\Services\ToolService\Examples\VersionCheckTool::class,
    ],
];
EOF
print_success "Streamable HTTP provider configured"

# Step 7: Install and configure Laravel Octane
print_step "Installing Laravel Octane..."
composer require laravel/octane --no-interaction
php artisan octane:install --server=frankenphp --no-interaction
print_success "Laravel Octane installed with FrankenPHP"

# Step 8: Skip Redis setup (not needed for Streamable HTTP)
print_step "Skipping Redis setup (not needed for Streamable HTTP)..."
print_success "Redis setup skipped"

# Step 9: Create test script for MCP HTTP testing
print_step "Creating MCP HTTP test script..."
cat > test-mcp.sh << 'EOF'
#!/bin/bash

# MCP HTTP Test Script
# This script tests the MCP server using curl with Streamable HTTP transport

set -e

# Get server port from file
if [ -f .server.port ]; then
    SERVER_PORT=$(cat .server.port)
    MCP_SERVER_URL="http://localhost:$SERVER_PORT"
    HTTP_ENDPOINT="$MCP_SERVER_URL/mcp"
    echo "🔗 Using server at port $SERVER_PORT"
else
    echo "❌ Server port file not found. Make sure the server is running."
    echo "   Run './start-server.sh' first to start the server."
    exit 1
fi

echo "🧪 Testing MCP Server with HTTP transport..."
echo "🔗 Server URL: $HTTP_ENDPOINT"

# Check if curl is available
if ! command -v curl &> /dev/null; then
    echo "❌ curl is required but not installed."
    exit 1
fi

echo ""
echo "🔧 Running MCP HTTP Tests..."
echo ""

# Test 1: Initialize handshake
echo "📡 Test 1: Initialize handshake"
curl -X POST "$HTTP_ENDPOINT" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "initialize",
    "params": {
      "protocolVersion": "2024-11-05",
      "capabilities": {
        "tools": {}
      },
      "clientInfo": {
        "name": "test-client",
        "version": "1.0.0"
      }
    }
  }' | jq '.' 2>/dev/null || echo "Response received (install jq for pretty printing)"

echo ""
echo ""

# Test 2: List tools
echo "📋 Test 2: List available tools"
curl -X POST "$HTTP_ENDPOINT" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 2,
    "method": "tools/list"
  }' | jq '.' 2>/dev/null || echo "Response received (install jq for pretty printing)"

echo ""
echo ""

# Test 3: Call hello_world tool
echo "👋 Test 3: Call hello_world tool"
curl -X POST "$HTTP_ENDPOINT" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 3,
    "method": "tools/call",
    "params": {
      "name": "hello_world",
      "arguments": {
        "name": "Test User"
      }
    }
  }' | jq '.' 2>/dev/null || echo "Response received (install jq for pretty printing)"

echo ""
echo ""

# Test 4: Call version_check tool
echo "🔍 Test 4: Call version_check tool"
curl -X POST "$HTTP_ENDPOINT" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 4,
    "method": "tools/call",
    "params": {
      "name": "version_check",
      "arguments": {}
    }
  }' | jq '.' 2>/dev/null || echo "Response received (install jq for pretty printing)"

echo ""
echo ""
echo "✅ All HTTP tests completed!"
echo ""
echo "💡 For interactive testing, use MCP Inspector:"
echo "   npx @modelcontextprotocol/inspector"
echo "   Connect to: $HTTP_ENDPOINT"
echo ""
EOF

chmod +x test-mcp.sh
print_success "MCP HTTP test script created"

# Step 10: Create startup script
print_step "Creating startup script..."
cat > start-server.sh << 'EOF'
#!/bin/bash

# MCP Server Startup Script

set -e

# Function to find available port
find_free_port() {
    local port
    for port in {8000..8999}; do
        if ! lsof -i :$port >/dev/null 2>&1; then
            echo $port
            return
        fi
    done
    echo "8080"  # fallback port
}

echo "🚀 Starting Laravel MCP Server..."

# Skip Redis check (not needed for Streamable HTTP)
echo "🚀 Using Streamable HTTP transport (Redis not required)"

# Find available port
SERVER_PORT=$(find_free_port)
echo "🔍 Found available port: $SERVER_PORT"

# Start Laravel Octane server in background
echo "🌐 Starting Laravel Octane server on http://localhost:$SERVER_PORT..."
php artisan octane:start --host=0.0.0.0 --port=$SERVER_PORT &
OCTANE_PID=$!
echo $OCTANE_PID > .octane.pid
echo $SERVER_PORT > .server.port
echo "✅ Octane server started (PID: $OCTANE_PID) on port $SERVER_PORT"

# Wait for server to be ready
echo "⏳ Waiting for server to be ready..."
for i in {1..30}; do
    if curl -s http://localhost:$SERVER_PORT/mcp > /dev/null 2>&1; then
        echo "✅ Server is ready at http://localhost:$SERVER_PORT"
        break
    fi
    if [ $i -eq 30 ]; then
        echo "❌ Server failed to start within 30 seconds"
        kill $OCTANE_PID 2>/dev/null || true
        rm -f .octane.pid .server.port
        exit 1
    fi
    sleep 1
done

EOF

chmod +x start-server.sh
print_success "Startup script created"

# Step 11: Create stop server script
print_step "Creating stop server script..."
cat > stop-server.sh << 'EOF'
#!/bin/bash

# MCP Server Stop Script

echo "🛑 Stopping Laravel MCP Server..."

# Stop Octane server
if [ -f .octane.pid ]; then
    OCTANE_PID=$(cat .octane.pid)
    if kill -0 $OCTANE_PID 2>/dev/null; then
        echo "🔴 Stopping Octane server (PID: $OCTANE_PID)..."
        kill $OCTANE_PID
        rm -f .octane.pid .server.port
        echo "✅ Octane server stopped"
    else
        echo "⚠️  Octane server was not running"
        rm -f .octane.pid .server.port
    fi
else
    echo "⚠️  No Octane PID file found, trying to stop any running Octane processes..."
    pkill -f "octane:start" || echo "No Octane processes found"
    rm -f .server.port
fi

# Optionally stop Redis (commented out by default to avoid affecting other services)
# echo "🔴 Stopping Redis server..."
# redis-cli shutdown || echo "Redis was not running or failed to stop"

echo "✅ Server stopped successfully"
EOF

chmod +x stop-server.sh
print_success "Stop server script created"

# Step 12: Create run-test script (all-in-one)
print_step "Creating all-in-one test runner..."
cat > run-test.sh << 'EOF'
#!/bin/bash

# All-in-one MCP Server Test Runner
# This script starts the server, runs tests, and stops the server

set -e

cleanup() {
    echo ""
    echo "🧹 Cleaning up..."
    ./stop-server.sh
    exit $1
}

# Set up cleanup on script exit
trap 'cleanup $?' EXIT INT TERM

echo "🧪 Starting MCP Server Test Suite..."
echo ""

# Start the server
echo "📡 Starting server..."
./start-server.sh
echo ""

# Run the tests
echo "🔬 Running MCP Inspector tests..."
echo ""
./test-mcp.sh

echo ""
echo "🎉 All tests completed successfully!"
echo "Server will be stopped automatically..."

EOF

chmod +x run-test.sh
print_success "All-in-one test runner created"

# Final instructions
print_step "Setup completed successfully!"
echo ""
printf "${GREEN}📁 Test project created in: ${PWD}${NC}\n"
echo ""
printf "${YELLOW}🎯 Next Steps:${NC}\n"
echo ""
printf "${GREEN}📁 First, navigate to the test directory:${NC}\n"
printf "   ${BLUE}cd ${TEST_DIR}${NC}\n"
echo ""
printf "🚀 ${GREEN}Quick Start (Recommended):${NC}\n"
printf "   ${BLUE}./run-test.sh${NC}\n"
printf "   ${GREEN}→ Starts server, shows test instructions, stops on Ctrl+C${NC}\n"
echo ""
printf "🔧 ${GREEN}Manual Control:${NC}\n"
printf "   ${BLUE}./start-server.sh${NC}    # Start server (auto-detects port)\n"
printf "   ${BLUE}./test-mcp.sh${NC}        # Show test instructions\n"
printf "   ${BLUE}./stop-server.sh${NC}     # Stop server\n"
echo ""
printf "🧪 ${GREEN}Direct Testing:${NC}\n"
printf "   ${BLUE}./start-server.sh && npx @modelcontextprotocol/inspector${NC}\n"
printf "   ${GREEN}→ Start server and open Inspector in browser${NC}\n"
echo ""
printf "${YELLOW}📋 Available Scripts:${NC}\n"
printf "   • ${BLUE}run-test.sh${NC}     - Complete test suite with auto-cleanup\n"
printf "   • ${BLUE}start-server.sh${NC} - Start MCP server (dynamic port)\n"
printf "   • ${BLUE}test-mcp.sh${NC}     - Show test instructions\n"
printf "   • ${BLUE}stop-server.sh${NC}  - Stop MCP server\n"
echo ""
printf "${YELLOW}📊 Endpoints:${NC}\n"
printf "   • HTTP: http://localhost:PORT/mcp (check PORT after running start-server.sh)\n"
echo ""
printf "${YELLOW}🔧 Troubleshooting:${NC}\n"
printf "   • Check server logs: ${BLUE}tail -f octane.log${NC}\n"
printf "   • Get server port: ${BLUE}cat .server.port${NC}\n"
printf "   • Test endpoint: ${BLUE}curl -I http://localhost:8000/mcp${NC} (update port after checking)\n"
echo ""
printf "${GREEN}🌐 Test with MCP Inspector:${NC}\n"
printf "   ${BLUE}npx @modelcontextprotocol/inspector${NC}\n"
echo ""
printf "${YELLOW}📋 Inspector Connection Steps:${NC}\n"
printf "   1. Inspector will open in your browser\n"
printf "   2. Get port number: ${BLUE}cat .server.port${NC}\n"
printf "   3. Enter URL: ${BLUE}http://localhost:PORT_NUMBER/mcp${NC}\n"
printf "   4. Click Connect button\n"
printf "   5. Success when tools/list and tools/call menus appear on the left!\n"
echo ""
printf "${GREEN}✨ Available Tools for Testing:${NC}\n"
printf "   • ${BLUE}hello_world${NC} - Greeting tool (requires 'name' parameter)\n"
printf "   • ${BLUE}version_check${NC} - Version checking tool (no parameters)\n"
