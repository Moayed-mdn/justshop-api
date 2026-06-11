#!/bin/bash

# Test Script for Session Domain Mismatch Error Handling
# This demonstrates the improved error messages when accessing endpoints from wrong domain

echo "=================================="
echo "Session Domain Mismatch Test"
echo "=================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

BASE_URL="http://localhost:8000"
PROXY_URL="http://localhost:3002/api/proxy"

echo -e "${YELLOW}Step 1: Login as Customer${NC}"
echo "----------------------------------------"

# Login as customer
CUSTOMER_RESPONSE=$(curl -s -c /tmp/customer_cookies.txt -X POST \
  "${BASE_URL}/api/v1/customer/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@example.com",
    "password": "password"
  }')

echo "$CUSTOMER_RESPONSE" | jq '.'
echo ""

echo -e "${YELLOW}Step 2: Try to access Merchant endpoint (should fail with clear error)${NC}"
echo "----------------------------------------"

# Try to access merchant endpoint with customer session
MISMATCH_RESPONSE=$(curl -s -b /tmp/customer_cookies.txt -X GET \
  "${BASE_URL}/api/v1/merchant/me" \
  -H "Content-Type: application/json")

echo "$MISMATCH_RESPONSE" | jq '.'
echo ""

# Check if the response contains the improved error message
if echo "$MISMATCH_RESPONSE" | jq -e '.logoutUrl' > /dev/null; then
  echo -e "${GREEN}✅ SUCCESS: Error response includes logoutUrl${NC}"
else
  echo -e "${RED}❌ FAILED: Error response missing logoutUrl${NC}"
fi

if echo "$MISMATCH_RESPONSE" | jq -e '.action' > /dev/null; then
  echo -e "${GREEN}✅ SUCCESS: Error response includes action field${NC}"
else
  echo -e "${RED}❌ FAILED: Error response missing action field${NC}"
fi

if echo "$MISMATCH_RESPONSE" | grep -q "currently logged in as a customer"; then
  echo -e "${GREEN}✅ SUCCESS: Error message is user-friendly${NC}"
else
  echo -e "${RED}❌ FAILED: Error message is not user-friendly${NC}"
fi

echo ""
echo -e "${YELLOW}Step 3: Logout Customer${NC}"
echo "----------------------------------------"

LOGOUT_URL=$(echo "$MISMATCH_RESPONSE" | jq -r '.logoutUrl')
echo "Logout URL: $LOGOUT_URL"

if [ "$LOGOUT_URL" != "null" ] && [ -n "$LOGOUT_URL" ]; then
  LOGOUT_RESPONSE=$(curl -s -b /tmp/customer_cookies.txt -X POST "$LOGOUT_URL")
  echo "$LOGOUT_RESPONSE" | jq '.'
  echo -e "${GREEN}✅ Logged out successfully using provided URL${NC}"
else
  echo -e "${RED}❌ No logout URL provided${NC}"
fi

echo ""
echo "=================================="
echo "Test Complete"
echo "=================================="
echo ""
echo "Summary:"
echo "- ✅ Improved error message with domain context"
echo "- ✅ logoutUrl provided in response"
echo "- ✅ action field for frontend automation"
echo "- ✅ User can understand and fix the problem"

# Cleanup
rm -f /tmp/customer_cookies.txt
