#!/bin/bash

# Nuclear Auth Flow Test Script
# Tests the complete authentication flow from command line

set -e

API_URL="http://localhost:8000"
COOKIE_FILE="/tmp/auth-test-cookies.txt"

echo "🧪 Testing Platform Authentication Flow"
echo "========================================"
echo ""

# Clean up old cookies
rm -f "$COOKIE_FILE"

# Step 1: Get CSRF cookie
echo "1️⃣  Getting CSRF cookie..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" \
  -c "$COOKIE_FILE" \
  "$API_URL/api/sanctum/csrf-cookie")

if [ "$HTTP_CODE" = "204" ]; then
  echo "   ✅ CSRF cookie obtained (204)"
else
  echo "   ❌ Failed to get CSRF cookie (HTTP $HTTP_CODE)"
  exit 1
fi

# Check if XSRF-TOKEN cookie exists
if grep -q "XSRF-TOKEN" "$COOKIE_FILE"; then
  echo "   ✅ XSRF-TOKEN cookie set"
else
  echo "   ❌ XSRF-TOKEN cookie missing"
  exit 1
fi

echo ""

# Step 2: Test debug endpoint (unauthenticated)
echo "2️⃣  Testing debug endpoint (before login)..."
curl -s -b "$COOKIE_FILE" "$API_URL/api/v1/platform/debug/session" | python3 -m json.tool | head -15
echo ""

# Step 3: Login
echo "3️⃣  Logging in..."
echo "   Email: admin@example.com"

LOGIN_RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" \
  -b "$COOKIE_FILE" -c "$COOKIE_FILE" \
  -X POST "$API_URL/api/v1/platform/auth/login" \
  -H "Content-Type: application/json" \
  -H "X-Requested-With: XMLHttpRequest" \
  -H "Accept: application/json" \
  -d '{"email":"admin@example.com","password":"password"}')

HTTP_CODE=$(echo "$LOGIN_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
RESPONSE_BODY=$(echo "$LOGIN_RESPONSE" | sed '/HTTP_CODE:/d')

if [ "$HTTP_CODE" = "200" ]; then
  echo "   ✅ Login successful (200)"
  echo "$RESPONSE_BODY" | python3 -m json.tool | grep -E '"message"|"email"' | head -3
else
  echo "   ❌ Login failed (HTTP $HTTP_CODE)"
  echo "$RESPONSE_BODY" | python3 -m json.tool
  exit 1
fi

echo ""

# Step 4: Test /auth/me
echo "4️⃣  Testing /auth/me..."
ME_RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" \
  -b "$COOKIE_FILE" \
  -H "X-Requested-With: XMLHttpRequest" \
  -H "Accept: application/json" \
  "$API_URL/api/v1/platform/auth/me")

HTTP_CODE=$(echo "$ME_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)

if [ "$HTTP_CODE" = "200" ]; then
  echo "   ✅ Auth check successful (200)"
  echo "$ME_RESPONSE" | sed '/HTTP_CODE:/d' | python3 -m json.tool | grep -E '"email"|"actor_context"|"auth_domain"' | head -5
else
  echo "   ❌ Auth check failed (HTTP $HTTP_CODE)"
  echo "$ME_RESPONSE" | sed '/HTTP_CODE:/d' | python3 -m json.tool
  exit 1
fi

echo ""

# Step 5: Test /dashboard
echo "5️⃣  Testing /dashboard..."
DASHBOARD_RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" \
  -b "$COOKIE_FILE" \
  -H "X-Requested-With: XMLHttpRequest" \
  -H "Accept: application/json" \
  "$API_URL/api/v1/platform/dashboard")

HTTP_CODE=$(echo "$DASHBOARD_RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)

if [ "$HTTP_CODE" = "200" ]; then
  echo "   ✅ Dashboard accessible (200)"
  echo "$DASHBOARD_RESPONSE" | sed '/HTTP_CODE:/d' | python3 -m json.tool | head -10
else
  echo "   ❌ Dashboard returned $HTTP_CODE (expected 200)"
  echo "$DASHBOARD_RESPONSE" | sed '/HTTP_CODE:/d' | python3 -m json.tool
  exit 1
fi

echo ""

# Step 6: Test debug endpoint (authenticated)
echo "6️⃣  Testing debug endpoint (after login)..."
curl -s -b "$COOKIE_FILE" \
  -H "X-Requested-With: XMLHttpRequest" \
  "$API_URL/api/v1/platform/debug/auth" | python3 -m json.tool

echo ""
echo "========================================"
echo "✅ ALL TESTS PASSED!"
echo ""
echo "Session cookies stored in: $COOKIE_FILE"
echo "You can inspect them with: cat $COOKIE_FILE"
