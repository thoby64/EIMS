#!/bin/bash

# Test MIME types and Accept header handling
# This verifies that Apache is correctly serving CSS/JS with proper Content-Type

URL="${1:-https://eims-rt8p.onrender.com}"

echo "=== Testing MIME Type Headers ==="
echo ""

# Test CSS file
echo "📄 Testing CSS MIME type:"
MANIFEST=$(curl -s "$URL/build/manifest.json")
CSS_FILE=$(echo "$MANIFEST" | grep -oP '"file":"[^"]*\.css"' | head -1 | cut -d'"' -f4)
if [ -n "$CSS_FILE" ]; then
    CSS_RESPONSE=$(curl -I "$URL/build/assets/$CSS_FILE" 2>/dev/null)
    echo "Request:"
    echo "  GET /build/assets/$CSS_FILE"
    echo ""
    echo "Response Headers:"
    echo "$CSS_RESPONSE" | grep -E "Content-Type|Cache-Control|Accept"
    echo ""
fi

# Test JavaScript file
echo "📄 Testing JavaScript MIME type:"
JS_FILE=$(echo "$MANIFEST" | grep -oP '"file":"[^"]*\.js"' | head -1 | cut -d'"' -f4)
if [ -n "$JS_FILE" ]; then
    JS_RESPONSE=$(curl -I "$URL/build/assets/$JS_FILE" 2>/dev/null)
    echo "Request:"
    echo "  GET /build/assets/$JS_FILE"
    echo ""
    echo "Response Headers:"
    echo "$JS_RESPONSE" | grep -E "Content-Type|Cache-Control|Accept"
    echo ""
fi

# Test HTML page
echo "📄 Testing HTML MIME type:"
HTML_RESPONSE=$(curl -I "$URL/login" 2>/dev/null)
echo "Request:"
echo "  GET /login"
echo ""
echo "Response Headers:"
echo "$HTML_RESPONSE" | grep -E "Content-Type|Cache-Control|Accept"
echo ""

echo "=== What to Look For ==="
echo "✅ CSS should have:   Content-Type: text/css"
echo "✅ JS should have:    Content-Type: application/javascript"
echo "✅ HTML should have:  Content-Type: text/html"
echo "✅ All should have:   Cache-Control headers"
echo ""
echo "❌ If you see 'text/plain' or no Content-Type, MIME types are wrong"
