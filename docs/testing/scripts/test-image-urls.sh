#!/bin/bash
# Quick test script to verify image URLs are correct

echo "Testing Product Image URLs..."
echo "=============================="
echo ""

echo "1. Testing Product Detail API (Storefront):"
curl -s http://localhost:8000/api/stores/2/products/34 | jq -r '.data.variants[0].media[0].url // "No media found"'
echo ""

echo "2. Testing Admin Product Detail API:"
curl -s http://localhost:8000/api/admin/products/34 | jq -r '.data.variants[0].media[0].url // "No media found"'
echo ""

echo "3. Checking for /storage/ prefix:"
curl -s http://localhost:8000/api/stores/2/products/34 | jq -r '.data.variants[0].media[].url' | grep -c "storage" || echo "Warning: No /storage/ found in URLs"
echo ""

echo "4. Testing Hero Banner (reference - should work):"
curl -s http://localhost:8000/api/stores/2/hero-banners | jq -r '.data[0].image_url // "No banner found"'
echo ""

echo "Done!"
