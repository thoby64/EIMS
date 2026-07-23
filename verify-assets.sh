#!/bin/bash

# EIMS Asset Loading Verification Script
# Tests that CSS/JS assets are loading correctly on Render deployment

set -e

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if URL provided
if [ -z "$1" ]; then
    echo -e "${RED}Usage: $0 <url>${NC}"
    echo "Example: $0 https://eims-rt8p.onrender.com"
    exit 1
fi

URL="$1"
LOGIN_PAGE="$URL/login"

echo -e "${YELLOW}=== EIMS Asset Loading Verification ===${NC}\n"

# Test 1: Check if page is accessible
echo -e "${YELLOW}[1] Checking page accessibility...${NC}"
if curl -s -o /dev/null -w "%{http_code}" "$LOGIN_PAGE" | grep -q "200"; then
    echo -e "${GREEN}✓ Login page accessible (HTTP 200)${NC}\n"
else
    echo -e "${RED}✗ Login page not accessible${NC}\n"
    exit 1
fi

# Test 2: Check for CSP header
echo -e "${YELLOW}[2] Checking Content-Security-Policy header...${NC}"
CSP_HEADER=$(curl -s -I "$LOGIN_PAGE" | grep -i "content-security-policy" || true)
if [ -n "$CSP_HEADER" ]; then
    echo -e "${GREEN}✓ CSP header present${NC}"
    echo "  $CSP_HEADER\n"
else
    echo -e "${YELLOW}⚠ CSP header not found (may be okay if set in Apache)${NC}\n"
fi

# Test 3: Check for Cache-Control on static assets
echo -e "${YELLOW}[3] Checking static asset caching...${NC}"
ASSETS_RESPONSE=$(curl -s -I "$URL/build/assets/" 2>/dev/null | head -5 || true)
if echo "$ASSETS_RESPONSE" | grep -q "Cache-Control"; then
    echo -e "${GREEN}✓ Cache-Control header found on assets${NC}\n"
else
    echo -e "${YELLOW}⚠ Cache-Control header not detected${NC}\n"
fi

# Test 4: Get list of assets from manifest
echo -e "${YELLOW}[4] Checking build manifest...${NC}"
MANIFEST_URL="$URL/build/manifest.json"
if curl -s "$MANIFEST_URL" | grep -q "app"; then
    echo -e "${GREEN}✓ Manifest file accessible${NC}"
    ASSET_FILES=$(curl -s "$MANIFEST_URL" | grep -o '"file":"[^"]*"' | head -3)
    echo "  Sample assets:"
    echo "$ASSET_FILES" | sed 's/^/    /'
    echo ""
else
    echo -e "${RED}✗ Manifest file not found or invalid${NC}\n"
fi

# Test 5: Check Content-Type of CSS file
echo -e "${YELLOW}[5] Checking CSS file headers...${NC}"
CSS_RESPONSE=$(curl -s -I "$URL/build/assets/" 2>/dev/null | grep "Content-Type" | head -1)
if echo "$CSS_RESPONSE" | grep -q "text/css\|application/javascript"; then
    echo -e "${GREEN}✓ Correct Content-Type headers${NC}\n"
else
    echo -e "${YELLOW}⚠ Content-Type: $(echo $CSS_RESPONSE | cut -d' ' -f2-)${NC}\n"
fi

# Test 6: Check for mixed content warnings in response
echo -e "${YELLOW}[6] Checking for mixed content policies...${NC}"
PAGE_CONTENT=$(curl -s "$LOGIN_PAGE")
if echo "$PAGE_CONTENT" | grep -qi "mixed-content"; then
    echo -e "${RED}✗ Mixed content policy detected${NC}\n"
else
    echo -e "${GREEN}✓ No mixed content policies detected${NC}\n"
fi

# Test 7: Check page includes asset links
echo -e "${YELLOW}[7] Checking asset links in page...${NC}"
ASSET_LINKS=$(echo "$PAGE_CONTENT" | grep -o 'href="[^"]*\.css"' | wc -l)
SCRIPT_LINKS=$(echo "$PAGE_CONTENT" | grep -o 'src="[^"]*\.js"' | wc -l)
echo -e "${GREEN}✓ Found $ASSET_LINKS CSS links and $SCRIPT_LINKS script links${NC}\n"

# Test 8: Check Apache modules (if we can)
echo -e "${YELLOW}[8] Testing application endpoints...${NC}"
HEALTH_RESPONSE=$(curl -s "$URL/health" 2>/dev/null || true)
if echo "$HEALTH_RESPONSE" | grep -q "healthy\|ok"; then
    echo -e "${GREEN}✓ Health check endpoint working${NC}\n"
else
    echo -e "${YELLOW}⚠ Health endpoint not responding (check app status)${NC}\n"
fi

# Summary
echo -e "${YELLOW}=== Summary ===${NC}"
echo -e "${GREEN}If all checks passed, assets should be loading correctly!${NC}"
echo ""
echo "Next steps:"
echo "1. Open DevTools (F12) in your browser"
echo "2. Go to Network tab"
echo "3. Reload the page (Ctrl+R)"
echo "4. Check that CSS and JS files show 200 status (not blocked)"
echo "5. Check that page displays with proper styling"
echo ""
echo "If CSS/JS still shows 'Mixed Block':"
echo "• Clear browser cache (Ctrl+Shift+Delete)"
echo "• Check browser console (F12 → Console)"
echo "• Verify HTTPS URL is being used"
