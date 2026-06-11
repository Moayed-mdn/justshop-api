# OpenCoder + Playwright MCP Testing Guide

## Overview

Use Playwright MCP with OpenCoder to interactively test all the new merchant pages and legacy redirects without writing formal E2E tests. This guide provides ready-to-use commands you can copy directly to OpenCoder.

---

## Prerequisites

Before you start, make sure:
1. ✅ Dev server is running: `npm run dev` (Port 4000)
2. ✅ Backend is running: `php artisan serve` (Port 8000)
3. ✅ Playwright MCP configured in OpenCoder
4. ✅ You have valid merchant credentials
5. ✅ At least one store with test data exists

---

## 🚀 Quick Start - Copy This to OpenCoder

**Copy this exact message** (replace with your real credentials):

```
Please test the routing standardization changes:

1. Login to http://localhost:4000/en/login with:
   - Email: [YOUR_EMAIL_HERE]
   - Password: [YOUR_PASSWORD_HERE]

2. Wait for redirect to merchant dashboard

3. Click on the store switcher and select the first store

4. Wait 2 seconds for store to activate

5. Test these 6 new pages and report status:
   - http://localhost:4000/en/merchant/categories/1/edit
   - http://localhost:4000/en/merchant/brands/1/edit
   - http://localhost:4000/en/merchant/tags/1/edit
   - http://localhost:4000/en/merchant/orders/1
   - http://localhost:4000/en/merchant/customers/1
   - http://localhost:4000/en/merchant/hero-banners/1/edit

6. Test these 4 legacy redirects (should auto-redirect):
   - http://localhost:4000/en/stores/1/categories/1/edit
   - http://localhost:4000/en/stores/1/brands/1/edit
   - http://localhost:4000/en/stores/1/tags/1/edit
   - http://localhost:4000/en/stores/1/products/1/edit

For each page report: URL, Status (✅/❌), Final URL (if redirected), and any console errors.

Create a summary table at the end.
```

**Don't forget to replace `[YOUR_EMAIL_HERE]` and `[YOUR_PASSWORD_HERE]`!**

---

## What OpenCoder Will Do

When you paste the command above, OpenCoder will:

1. ✅ Open browser automatically
2. ✅ Navigate to login page
3. ✅ Fill in your credentials
4. ✅ Click login button
5. ✅ Wait for redirect to dashboard
6. ✅ Click store switcher dropdown
7. ✅ Select the first store
8. ✅ Visit each URL one by one
9. ✅ Check for JavaScript errors
10. ✅ Report results in a nice table
11. ✅ Take screenshots if errors found

**Total time: ~30-40 seconds for all 10 pages!** 🚀

---

## Testing Without Authentication?

**No, you must provide credentials.** The pages are protected and will redirect to login without authentication. Playwright MCP cannot bypass authentication because:
- Auth tokens are stored in cookies/localStorage
- Protected routes redirect to login
- API calls fail without valid JWT tokens

**Just include your email/password in the prompt above.**

---

## Alternative Testing Options

### Option 1: Step-by-Step Testing

If you want to test one page at a time:

#### Step 1: Login First
```
Navigate to http://localhost:4000/en/login and login with:
- Email: your-email@example.com
- Password: your-password

After successful login, select Store 1 from the store switcher.
```

Wait for OpenCoder to complete this, then:

#### Step 2: Test Individual Page
```
Navigate to http://localhost:4000/en/merchant/categories/1/edit

Check:
1. Does the page load without errors? (status 200)
2. Is there a form visible on the page?
3. Are there any JavaScript errors in the console?
4. Take a screenshot

Report the results.
```

Repeat for each page you want to test.

---

### Option 2: Quick Status Check (No Screenshots)

If you just want HTTP status codes:

```
Please check which of these URLs return 200 OK after logging in:

Login: http://localhost:4000/en/login
Email: [your-email]
Password: [your-password]

Then select Store 1 and check:
1. http://localhost:4000/en/merchant/categories/1/edit
2. http://localhost:4000/en/merchant/brands/1/edit
3. http://localhost:4000/en/merchant/tags/1/edit
4. http://localhost:4000/en/merchant/orders/1
5. http://localhost:4000/en/merchant/customers/1
6. http://localhost:4000/en/merchant/hero-banners/1/edit

Report each URL with its status code.
```

---

## Understanding OpenCoder's Response

### ✅ Successful Test
```
✅ Test Results:

Login: ✅ Success
Store Selection: ✅ Store 1 activated

Page Tests:
- /en/merchant/categories/1/edit → 200 OK ✅
- /en/merchant/brands/1/edit → 200 OK ✅
- /en/merchant/tags/1/edit → 200 OK ✅
- /en/merchant/orders/1 → 200 OK ✅
- /en/merchant/customers/1 → 200 OK ✅
- /en/merchant/hero-banners/1/edit → 200 OK ✅

Legacy Redirects:
- /en/stores/1/categories/1/edit → 302 → /en/merchant/categories/1/edit ✅
- /en/stores/1/brands/1/edit → 302 → /en/merchant/brands/1/edit ✅
- /en/stores/1/tags/1/edit → 302 → /en/merchant/tags/1/edit ✅
- /en/stores/1/products/1/edit → 302 → /en/merchant/products/1/edit ✅

All tests passed! No JavaScript errors found.
```

### ❌ Test with Issues
```
⚠️ Test Results:

Login: ✅ Success
Store Selection: ✅ Store 1 activated

Page Tests:
- /en/merchant/categories/1/edit → 404 Not Found ❌
  Error: Cannot GET /en/merchant/categories/1/edit
  Screenshot saved: error-categories.png

Console Error:
  TypeError: ROUTES.merchant.categories.edit is not a function
  at EditCategoryPage.tsx:12
```

---

## Detailed Test Scenarios

Use these for thorough testing of specific features:

### Scenario 1: Test New Category Edit Page

```
Navigate to http://localhost:4000/en/merchant/categories/1/edit

Check that:
1. The page loads without errors (status 200)
2. The page title shows "Edit Category" or similar
3. A form is visible with category fields
4. There's a "Save" or "Update" button
5. There's a delete/danger zone section at the bottom
6. No "No active store" error is shown
```

**Expected Result**: ✅ Edit form loads successfully

---

### Scenario 2: Test New Brand Edit Page

```
Navigate to http://localhost:4000/en/merchant/brands/1/edit

Check that:
1. The page loads without errors (status 200)
2. The edit form is visible
3. Brand fields are present (name, logo, etc.)
4. Save button is present
5. Danger zone is at the bottom
```

**Expected Result**: ✅ Edit form loads successfully

---

### Scenario 3: Test New Tag Edit Page

```
Navigate to http://localhost:4000/en/merchant/tags/1/edit

Check that:
1. The page loads without errors (status 200)
2. Tag edit form is visible
3. Form fields are present
4. Save button exists
```

**Expected Result**: ✅ Edit form loads successfully

---

### Scenario 4: Test New Order Detail Page

```
Navigate to http://localhost:4000/en/merchant/orders/1

Check that:
1. The page loads without errors (status 200)
2. Order details are displayed (order number, date, status)
3. Order items table is visible
4. Order status dropdown/selector is present
5. Customer information is shown
```

**Expected Result**: ✅ Order detail view loads successfully

---

### Scenario 5: Test New Customer Detail Page

```
Navigate to http://localhost:4000/en/merchant/customers/1

Check that:
1. The page loads without errors (status 200)
2. Customer information is displayed
3. Customer details card is visible
```

**Expected Result**: ✅ Customer detail view loads successfully

---

### Scenario 6: Test Hero Banner Edit (Standardized)

```
Navigate to http://localhost:4000/en/merchant/hero-banners/1/edit

Check that:
1. The page loads without errors (status 200)
2. Hero banner edit form is visible
3. Visual type selector is present
4. Translation tabs (EN/AR) are visible
5. Save button exists
```

**Expected Result**: ✅ Hero banner edit loads successfully

---

### Scenario 7: Test Hero Banner Legacy Redirect

```
Navigate to http://localhost:4000/en/merchant/hero-banners/1

Check that:
1. The page shows a loading/redirect message
2. The URL automatically changes to /en/merchant/hero-banners/1/edit
3. The edit form loads after redirect
```

**Expected Result**: ✅ Auto-redirect to /edit route

---

### Scenario 8: Test Legacy Category Edit Redirect

```
Navigate to http://localhost:4000/en/stores/1/categories/1/edit

Check that:
1. A "Switching Workspace Context" message appears
2. The URL redirects to /en/merchant/categories/1/edit
3. The edit form loads after redirect
4. Active store is set to Store 1
```

**Expected Result**: ✅ Legacy route redirects with context hydration

---

### Scenario 9: Test Legacy Brand Edit Redirect

```
Navigate to http://localhost:4000/en/stores/1/brands/1/edit

Check that:
1. Redirect process initiates
2. URL changes to /en/merchant/brands/1/edit
3. Brand edit form loads
```

**Expected Result**: ✅ Legacy route redirects properly

---

### Scenario 10: Test Legacy Tag Edit Redirect

```
Navigate to http://localhost:4000/en/stores/1/tags/1/edit

Check that:
1. Redirect process initiates
2. URL changes to /en/merchant/tags/1/edit
3. Tag edit form loads
```

**Expected Result**: ✅ Legacy route redirects properly

---

### Scenario 11: Test Legacy Product Edit Redirect

```
Navigate to http://localhost:4000/en/stores/1/products/1/edit

Check that:
1. Redirect process initiates
2. URL changes to /en/merchant/products/1/edit
3. Product edit form loads
```

**Expected Result**: ✅ Legacy route redirects properly

---

### Scenario 12: Test Sidebar Navigation

```
From the merchant dashboard, check the sidebar:

1. Click on "Categories" link
   Expected: Navigate to /en/merchant/categories

2. Click on "Brands" link
   Expected: Navigate to /en/merchant/brands

3. Click on "Tags" link
   Expected: Navigate to /en/merchant/tags

4. Click on "Orders" link
   Expected: Navigate to /en/merchant/orders

5. Click on "Users" or "Customers" link
   Expected: Navigate to /en/merchant/customers
```

**Expected Result**: ✅ All sidebar links work correctly

---

### Scenario 13: Test Empty State (No Active Store)

```
1. Clear active store from session/storage (if possible)
2. Navigate to http://localhost:4000/en/merchant/categories/1/edit

Check that:
1. Page loads without crashing
2. "No active store" message is displayed
3. Message says "Select a store from the switcher"
4. No edit form is visible
```

**Expected Result**: ✅ Empty state displays correctly

---

### Scenario 14: Test Invalid ID (404 Handling)

```
Navigate to http://localhost:4000/en/merchant/categories/999999/edit

Check that:
1. Page loads without crashing
2. Error message is displayed (category not found)
3. No JavaScript errors in console
```

**Expected Result**: ✅ Error state handled gracefully

---

### Scenario 15: Test Loading State

```
Navigate to http://localhost:4000/en/merchant/categories/1/edit

While page is loading, check that:
1. A skeleton loader or loading message appears
2. The page doesn't show blank content
3. Loading indicator disappears when data loads
```

**Expected Result**: ✅ Loading state shows properly

---

## Quick Test Script for OpenCoder

Copy and paste this to OpenCoder with Playwright MCP:

```
Please test the following merchant pages on http://localhost:4000:

1. First, login at /en/login with [your-credentials]
2. Select Store ID 1 from the store switcher
3. Then navigate to each of these URLs and verify they load successfully:

   - /en/merchant/categories/1/edit (should show category edit form)
   - /en/merchant/brands/1/edit (should show brand edit form)
   - /en/merchant/tags/1/edit (should show tag edit form)
   - /en/merchant/orders/1 (should show order details)
   - /en/merchant/customers/1 (should show customer details)
   - /en/merchant/hero-banners/1/edit (should show hero banner edit form)

4. Then test legacy redirects:

   - /en/stores/1/categories/1/edit (should redirect to /en/merchant/categories/1/edit)
   - /en/stores/1/brands/1/edit (should redirect to /en/merchant/brands/1/edit)
   - /en/stores/1/tags/1/edit (should redirect to /en/merchant/tags/1/edit)
   - /en/stores/1/products/1/edit (should redirect to /en/merchant/products/1/edit)

For each page, verify:
- No 404 errors
- No JavaScript errors in console
- Form or content is visible
- No "No active store" error (since we selected a store)

Take screenshots of any errors found.
```

---

## Automated Check Script

Ask OpenCoder to run this:

```
Please visit these URLs in sequence and report which ones return 200 OK vs errors:

New merchant pages:
1. http://localhost:4000/en/merchant/categories/1/edit
2. http://localhost:4000/en/merchant/brands/1/edit
3. http://localhost:4000/en/merchant/tags/1/edit
4. http://localhost:4000/en/merchant/orders/1
5. http://localhost:4000/en/merchant/customers/1
6. http://localhost:4000/en/merchant/hero-banners/1/edit

Legacy redirects (should redirect, not 404):
7. http://localhost:4000/en/stores/1/categories/1/edit
8. http://localhost:4000/en/stores/1/brands/1/edit
9. http://localhost:4000/en/stores/1/tags/1/edit
10. http://localhost:4000/en/stores/1/products/1/edit

Create a summary table showing URL, Status Code, and any errors.
```

---

## What to Look For

### ✅ Success Indicators
- HTTP 200 status
- No console errors
- Forms/content visible
- Proper redirects (302 → 200)
- Loading states appear and disappear
- Active store context preserved

### ❌ Failure Indicators
- 404 Not Found errors
- JavaScript TypeError in console
- "No active store" when store is selected
- Blank pages
- Infinite redirects
- Missing components

---

## Common Issues & Solutions

### Issue: "No active store" despite selecting store
**Solution**: Check that `useBootstrapStore` is properly initialized

### Issue: 404 on merchant routes
**Solution**: Verify file exists in correct directory structure

### Issue: Legacy routes don't redirect
**Solution**: Check `LegacyRouteRedirector` is imported and used correctly

### Issue: Infinite redirect loop
**Solution**: Ensure redirect target URL is different from source URL

---

## Test Data Requirements

For successful testing, ensure you have:
- ✅ At least 1 category (ID: 1)
- ✅ At least 1 brand (ID: 1)
- ✅ At least 1 tag (ID: 1)
- ✅ At least 1 order (ID: 1)
- ✅ At least 1 customer/user (ID: 1)
- ✅ At least 1 hero banner (ID: 1)
- ✅ At least 1 product (ID: 1)

If data is missing, you'll get "not found" errors (which should be handled gracefully).

---

## Sample OpenCoder Conversation

**You:**
```
I need to test the new merchant routing pages. Please:

1. Navigate to http://localhost:4000/en/login
2. Login with email "merchant@example.com" and password "password"
3. After login, select "Store 1" from the store switcher
4. Then visit /en/merchant/categories/1/edit and tell me if it loads
```

**OpenCoder will:**
- Open the browser
- Navigate to login
- Fill in credentials
- Submit form
- Wait for redirect
- Click store switcher
- Select store
- Navigate to category edit
- Report status and take screenshot

---

## Batch Testing Command

For faster testing, ask OpenCoder:

```
Please create a test report for all new merchant pages:

1. Login to http://localhost:4000/en/login
2. Select Store 1
3. Visit each of these pages and create a table with columns:
   - URL
   - Status (✅ Success / ❌ Error)
   - Loading Time
   - Screenshot Path

Pages to test:
- /en/merchant/categories/1/edit
- /en/merchant/brands/1/edit
- /en/merchant/tags/1/edit
- /en/merchant/orders/1
- /en/merchant/customers/1
- /en/merchant/hero-banners/1/edit
- /en/stores/1/categories/1/edit (legacy)
- /en/stores/1/brands/1/edit (legacy)
- /en/stores/1/tags/1/edit (legacy)
- /en/stores/1/products/1/edit (legacy)

Save screenshots to: /tmp/routing-tests/
```

---

## Expected Test Results

| Page | Status | Expected Behavior |
|------|--------|-------------------|
| Categories Edit | ✅ 200 | Edit form loads |
| Brands Edit | ✅ 200 | Edit form loads |
| Tags Edit | ✅ 200 | Edit form loads |
| Orders Detail | ✅ 200 | Order details display |
| Customers Detail | ✅ 200 | Customer info displays |
| Hero Banners Edit | ✅ 200 | Edit form loads |
| Legacy Categories | ✅ 302→200 | Redirects to merchant route |
| Legacy Brands | ✅ 302→200 | Redirects to merchant route |
| Legacy Tags | ✅ 302→200 | Redirects to merchant route |
| Legacy Products | ✅ 302→200 | Redirects to merchant route |

---

## Post-Testing Checklist

After OpenCoder completes testing:

- [ ] All new merchant pages return 200
- [ ] All edit forms are visible
- [ ] All detail pages show content
- [ ] All legacy routes redirect properly
- [ ] No JavaScript console errors
- [ ] No 404 errors
- [ ] Loading states work
- [ ] Empty states work (when store not selected)
- [ ] Sidebar navigation links work

---

## Benefits of Using Playwright MCP

1. ✅ **No test files to write** - Just conversational testing
2. ✅ **Visual feedback** - Get screenshots automatically
3. ✅ **Real browser testing** - Tests actual user experience
4. ✅ **Fast iteration** - Immediate feedback on issues
5. ✅ **Console output** - See JavaScript errors in real-time
6. ✅ **Network monitoring** - Track API calls and responses

---

## Summary

Instead of writing formal E2E tests, use Playwright MCP interactively:

1. Ask OpenCoder to navigate to pages
2. Verify expected behavior
3. Get screenshots and error reports
4. Fix any issues found
5. Re-test quickly

This approach is perfect for:
- ✅ Quick verification of new features
- ✅ Manual exploratory testing
- ✅ One-time migration testing
- ✅ Before writing formal E2E tests

For production, you may still want to write formal E2E tests later, but for now, this gets you fast feedback!

---

**Ready to Test!** 🎭

Just ask OpenCoder with Playwright MCP to follow any of the scenarios above, and it will interactively test your pages.
