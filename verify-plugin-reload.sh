#!/bin/bash
#
# Quick Plugin Reload Verification Script
# Run this script to check if WordPress has loaded the updated plugin files
#
# Usage: bash verify-plugin-reload.sh
#

echo "=========================================="
echo "Alibeyg Citynet Plugin Reload Checker"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if curl is available
if ! command -v curl &> /dev/null; then
    echo -e "${RED}Error: curl is not installed${NC}"
    exit 1
fi

# Test the version endpoint
echo "Testing version endpoint..."
echo ""

RESPONSE=$(curl -s "https://alibeyg.com.iq/wp-json/alibeyg/v1/version")

if [ $? -ne 0 ]; then
    echo -e "${RED}✗ Failed to connect to version endpoint${NC}"
    echo "  Make sure the site is accessible"
    exit 1
fi

# Check if response is valid JSON
if ! echo "$RESPONSE" | jq . &> /dev/null; then
    echo -e "${RED}✗ Invalid response from server${NC}"
    echo "  Response: $RESPONSE"
    exit 1
fi

echo "Raw response:"
echo "$RESPONSE" | jq .
echo ""
echo "=========================================="
echo "Verification Results:"
echo "=========================================="

# Extract values
PLUGIN_VERSION=$(echo "$RESPONSE" | jq -r '.plugin_version // "unknown"')
API_BASE=$(echo "$RESPONSE" | jq -r '.api_base // "unknown"')
TIMEOUT=$(echo "$RESPONSE" | jq -r '.flight_search_timeout // "unknown"')
RETRY_ENABLED=$(echo "$RESPONSE" | jq -r '.retry_enabled // false')
MAX_RETRIES=$(echo "$RESPONSE" | jq -r '.max_retries // 0')

# Check plugin version
echo -n "Plugin Version: "
if [ "$PLUGIN_VERSION" == "0.5.1" ]; then
    echo -e "${GREEN}✓ $PLUGIN_VERSION (CORRECT)${NC}"
else
    echo -e "${RED}✗ $PLUGIN_VERSION (Should be 0.5.1)${NC}"
fi

# Check API base
echo -n "API Base URL: "
if [[ "$API_BASE" == *"171.22.24.69"* ]]; then
    echo -e "${GREEN}✓ $API_BASE (CORRECT)${NC}"
else
    echo -e "${RED}✗ $API_BASE (Should be https://171.22.24.69/api/v1.0/)${NC}"
fi

# Check timeout
echo -n "Flight Search Timeout: "
if [[ "$TIMEOUT" == *"60"* ]]; then
    echo -e "${GREEN}✓ $TIMEOUT (CORRECT)${NC}"
else
    echo -e "${RED}✗ $TIMEOUT (Should be 60 seconds)${NC}"
fi

# Check retry
echo -n "Retry Logic: "
if [ "$RETRY_ENABLED" == "true" ]; then
    echo -e "${GREEN}✓ Enabled with $MAX_RETRIES retries (CORRECT)${NC}"
else
    echo -e "${RED}✗ Disabled (Should be enabled)${NC}"
fi

echo ""
echo "=========================================="

# Final verdict
if [ "$PLUGIN_VERSION" == "0.5.1" ] && [[ "$API_BASE" == *"171.22.24.69"* ]] && [[ "$TIMEOUT" == *"60"* ]]; then
    echo -e "${GREEN}✓ PLUGIN LOADED CORRECTLY!${NC}"
    echo ""
    echo "The plugin has been updated successfully."
    echo "If you're still experiencing timeout issues, they may be due to:"
    echo "  - Slow API response from 171.22.24.69"
    echo "  - Network connectivity issues"
    echo "  - API server being down"
    echo ""
else
    echo -e "${RED}✗ PLUGIN NOT UPDATED${NC}"
    echo ""
    echo "WordPress is still using the old plugin code."
    echo "Please follow these steps:"
    echo ""
    echo "1. Deactivate and reactivate the plugin:"
    echo "   WordPress Admin → Plugins → Deactivate 'Alibeyg Citynet Bridge'"
    echo "   Wait 3 seconds"
    echo "   Click 'Activate'"
    echo ""
    echo "2. Flush OPcache (most important!):"
    echo "   sudo systemctl restart php*-fpm"
    echo "   OR visit: https://alibeyg.com.iq/flush-opcache.php"
    echo ""
    echo "3. Clear all WordPress caches:"
    echo "   wp cache flush"
    echo "   wp transient delete --all"
    echo ""
    echo "4. Run this script again to verify"
    echo ""
    echo "See PLUGIN-RELOAD-GUIDE.md for detailed instructions."
fi

echo "=========================================="
