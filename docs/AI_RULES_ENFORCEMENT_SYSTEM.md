# 🤖 AI Rules Enforcement System

**Purpose**: Ensure AI assistants strictly follow the project's architectural rules  
**Authority**: ARCHITECTURE.md is the supreme law  
**Date**: June 7, 2026

---

## 🎯 Core Principle

**AI MUST follow the rules defined in ARCHITECTURE.md and related governance documents WITHOUT EXCEPTION.**

This document teaches you how to make AI assistants respect your project's rules.

---

## 📋 Table of Contents

1. [How to Make AI Follow Rules](#how-to-make-ai-follow-rules)
2. [AI Prompt Templates](#ai-prompt-templates)
3. [Rule Enforcement Checklist](#rule-enforcement-checklist)
4. [Common AI Mistakes & Prevention](#common-ai-mistakes--prevention)
5. [Code Review Checklist for AI Output](#code-review-checklist)

---

## 1. How to Make AI Follow Rules

### Method 1: Explicit Rule Reference (BEST)

When asking AI to do something, **always reference the rules document**:

```
Read /home/leader/projects/laravel/v3/tenant/laratenant-backend/docs/ARCHITECTURE.md 
and strictly follow ALL rules while implementing [your task].

Critical rules to follow:
- No database enums (use PHP enums only)
- Store scoping MANDATORY for all queries
- DTOs required with storeId as first parameter
- Thin controllers (10-15 lines)
- Authorization ONLY in Policies
- Actions MUST NOT check permissions

Now implement: [your specific task]
```

### Method 2: Provide Rules as Context

Copy the critical rules into your prompt:

```
MANDATORY RULES (from ARCHITECTURE.md):

1. Database Enum Rule: FORBIDDEN
   ❌ $table->enum('status', ['pending', 'paid'])
   ✅ $table->string('status') + PHP Enum

2. Store Scoping: MANDATORY
   ❌ Product::find($id)
   ✅ Product::where('store_id', $storeId)->findOrFail($id)

3. Authorization: ONLY in Policies
   ❌ Actions checking permissions
   ✅ Controllers call $this->authorize()

4. DTOs: MANDATORY
   - storeId MUST be first parameter
   - Strongly typed
   - Provide fromRequest() factory

Now implement: [your task]
```

### Method 3: Ask AI to Extract and Confirm Rules

```
1. Read ARCHITECTURE.md
2. Extract the rules relevant to [domain/feature]
3. List them for my confirmation
4. Then implement following those rules strictly
```

---

## 2. AI Prompt Templates

### Template 1: New Feature Implementation

```
I need to implement [feature name] for the Laravel API.

STRICT REQUIREMENTS:

1. Read and follow ALL rules in:
   - /laratenant-backend/docs/ARCHITECTURE.md
   - /laratenant-backend/docs/EXECUTION_GOVERNANCE.md

2. Architecture compliance checklist:
   ✓ Domain-first folder structure (app/Actions/{Domain}/)
   ✓ No database enums (use PHP enums)
   ✓ Store scoping on ALL queries
   ✓ DTO with storeId as first parameter
   ✓ Thin controller (10-15 lines max)
   ✓ Authorization ONLY in Policy
   ✓ No business logic in Models
   ✓ Repository for database access
   ✓ Action for business logic
   ✓ FormRequest for validation
   ✓ Resource for API response
   ✓ ApiResponserTrait for responses
   ✓ Localization for all messages

3. Feature: [describe your feature]

4. Acceptance criteria:
   - [criterion 1]
   - [criterion 2]

5. BEFORE writing code, confirm:
   - Which domain does this belong to?
   - What files need to be created?
   - Are all rules satisfied?

6. Then implement following the Golden Path.
```

### Template 2: Bug Fix

```
I need to fix [bug description].

MANDATORY PROCESS:

1. Read ARCHITECTURE.md and EXECUTION_GOVERNANCE.md
2. Identify which architectural layer has the bug
3. Propose the fix while maintaining ALL rules:
   - Store scoping intact
   - Authorization in Policy only
   - No enum violations
   - DTO integrity maintained
   - No business logic in Controller

4. Show me the fix for approval before applying

Bug details: [describe bug]
```

### Template 3: Refactoring

```
I need to refactor [component] to be architecture-compliant.

STRICT REQUIREMENTS:

1. Read ARCHITECTURE.md sections on:
   - [relevant sections]

2. Current violations to fix:
   - [violation 1]
   - [violation 2]

3. Target state:
   - 100% ARCHITECTURE.md compliant
   - All rules followed
   - No regressions

4. Show me the refactoring plan first
5. Then implement with my approval

Component: [describe what to refactor]
```

### Template 4: Code Review by AI

```
Review this code against ARCHITECTURE.md rules:

[paste code here]

Check for violations of:
1. Database enum rule
2. Store scoping rule  
3. Authorization doctrine (Policy-only)
4. DTO requirements
5. Controller thickness
6. Domain structure
7. Repository usage
8. Localization
9. Error handling
10. API response format

List ALL violations found and suggest fixes.
```

---

## 3. Rule Enforcement Checklist

Use this checklist when asking AI to implement anything:

### ✅ Pre-Implementation Checklist

- [ ] AI has read ARCHITECTURE.md
- [ ] AI has confirmed which domain this belongs to
- [ ] AI understands the Golden Path flow
- [ ] AI knows the folder structure rules
- [ ] AI confirmed no database enum usage
- [ ] AI confirmed store scoping strategy
- [ ] AI confirmed DTO structure with storeId

### ✅ During Implementation Checklist

- [ ] Files created in correct domain folders
- [ ] No database enum() in migrations
- [ ] All queries include `where('store_id', $storeId)`
- [ ] DTO created with storeId as first parameter
- [ ] Controller is thin (10-15 lines)
- [ ] Authorization only in Policy
- [ ] Action has no authorization logic
- [ ] Repository used for database access
- [ ] FormRequest used for validation
- [ ] Resource used for API response
- [ ] ApiResponserTrait used for response
- [ ] All messages use localization __()
- [ ] Error codes use ErrorCode enum
- [ ] Custom exceptions used where needed

### ✅ Post-Implementation Checklist

- [ ] Code follows Golden Path
- [ ] All ARCHITECTURE.md rules followed
- [ ] No anti-patterns present
- [ ] Domain isolation maintained
- [ ] Store scoping verified
- [ ] Policy authorization confirmed
- [ ] Tests written (if applicable)
- [ ] Documentation updated

---

## 4. Common AI Mistakes & Prevention

### ❌ Mistake 1: Using Database Enums

**What AI does wrong:**
```php
$table->enum('status', ['pending', 'paid', 'failed']);
```

**Prevention prompt:**
```
CRITICAL: Database enums are ABSOLUTELY FORBIDDEN.
Use string column + PHP enum ONLY.

Correct approach:
1. Migration: $table->string('status')
2. Enum: enum OrderStatusEnum: string { case PENDING = 'pending'; }
3. Model cast: 'status' => OrderStatusEnum::class
```

---

### ❌ Mistake 2: Missing Store Scoping

**What AI does wrong:**
```php
Product::find($id);
Order::where('status', 'pending')->get();
```

**Prevention prompt:**
```
CRITICAL: ALL commerce domain queries MUST include store_id.

❌ FORBIDDEN: Product::find($id)
✅ REQUIRED: Product::where('store_id', $storeId)->findOrFail($id)

❌ FORBIDDEN: Order::where('status', 'pending')->get()
✅ REQUIRED: Order::where('store_id', $storeId)->where('status', 'pending')->get()

Exception: CMS platform content only (CmsDocument, MarketingPage)
```

---

### ❌ Mistake 3: Authorization in Actions

**What AI does wrong:**
```php
// Inside Action
if (!Auth::user()->hasRole('admin')) {
    throw new UnauthorizedException();
}
```

**Prevention prompt:**
```
CRITICAL: Actions MUST NEVER check authorization.

Authorization ONLY in:
- Controllers: $this->authorize('update', $product)
- Policies: ProductPolicy

Actions assume authorization passed.
Actions receive actorId, storeId explicitly.

NO auth() or Auth::user() in Actions!
```

---

### ❌ Mistake 4: Fat Controllers

**What AI does wrong:**
```php
public function store(Request $request) {
    // 50 lines of business logic
    // Database queries
    // Validation
    // Error handling
}
```

**Prevention prompt:**
```
CRITICAL: Controllers MUST be thin (10-15 lines max)

Required pattern:
1. Receive FormRequest (validation done)
2. Create DTO from request
3. Call Action
4. Return Resource via ApiResponserTrait

Example:
public function store(CreateProductRequest $request, int $store): JsonResponse {
    $product = $this->createProductAction->execute(
        CreateProductDTO::fromRequest($request, $store)
    );
    return $this->success(new ProductResource($product));
}
```

---

### ❌ Mistake 5: Missing DTO or Wrong DTO Structure

**What AI does wrong:**
```php
// No DTO, passing array
$action->execute([
    'name' => $request->name,
    'store_id' => $store,
]);

// Or: storeId not first parameter
public function __construct(
    public string $name,
    public int $storeId,  // ❌ Should be first!
) {}
```

**Prevention prompt:**
```
CRITICAL: DTOs are MANDATORY with specific structure:

1. storeId MUST be first constructor parameter
2. Must be strongly typed
3. Must provide fromRequest() factory
4. Must be immutable

Correct:
public function __construct(
    public int $storeId,      // ✅ First parameter
    public string $name,
    public int $productId,
) {}

public static function fromRequest(Request $request, int $storeId): self {
    return new self(
        storeId: $storeId,     // From route parameter
        name: $request->string('name'),
        productId: $request->integer('product_id'),
    );
}
```

---

### ❌ Mistake 6: Direct Model Access Instead of Repository

**What AI does wrong:**
```php
// In Controller or Action
$products = Product::where('store_id', $storeId)->get();
```

**Prevention prompt:**
```
CRITICAL: Database access ONLY through Repositories.

❌ FORBIDDEN in Controllers/Actions:
Product::where(...)->get()
DB::table('products')->...
Model::create([...])

✅ REQUIRED:
- Create ProductRepository
- All queries in repository
- Controllers/Actions call repository methods

Example:
class ProductRepository {
    public function findByStore(int $storeId): Collection {
        return Product::where('store_id', $storeId)->get();
    }
}
```

---

### ❌ Mistake 7: Hardcoded Strings (No Localization)

**What AI does wrong:**
```php
throw new Exception('Product not found');
return $this->error('Invalid product');
```

**Prevention prompt:**
```
CRITICAL: ALL user-facing messages MUST use localization.

❌ FORBIDDEN: 'Product not found'
✅ REQUIRED: __('product.not_found')

❌ FORBIDDEN: throw new Exception('Invalid data')
✅ REQUIRED: throw new ValidationException(__('validation.invalid_data'))

All messages go in lang/en/ and lang/ar/
```

---

### ❌ Mistake 8: Wrong Folder Structure

**What AI does wrong:**
```
app/Actions/CreateProductAction.php  ❌ Flat structure
app/DTOs/ProductDTO.php              ❌ Flat structure
```

**Prevention prompt:**
```
CRITICAL: Domain-first structure MANDATORY.

❌ WRONG:
app/Actions/CreateProductAction.php
app/DTOs/ProductDTO.php

✅ CORRECT:
app/Actions/Product/CreateProductAction.php
app/DTOs/Product/CreateProductDTO.php

Rule: Domain BEFORE type.
Every file belongs to a domain (Product, Cart, Order, Auth, etc.)
```

---

## 5. Code Review Checklist for AI Output

When AI gives you code, verify these points:

### Database Layer
- [ ] No `enum()` in migrations
- [ ] All columns use appropriate types (string for enums)
- [ ] Foreign keys properly defined
- [ ] Indexes on `store_id` where needed

### Models
- [ ] Enums cast in $casts array
- [ ] No business logic in models
- [ ] Relationships defined correctly
- [ ] Fillable/guarded defined

### Enums
- [ ] PHP enum used (not database enum)
- [ ] Backed by string or int
- [ ] values() method available for validation

### DTOs
- [ ] storeId is first parameter
- [ ] All properties strongly typed
- [ ] fromRequest() factory provided
- [ ] Immutable (readonly or no setters)

### Repositories
- [ ] ALL queries scoped by store_id
- [ ] Only database access (no business logic)
- [ ] Returns Models or Collections

### Actions
- [ ] Single responsibility
- [ ] Accepts DTO
- [ ] NO authorization logic
- [ ] NO Auth::user() or auth() calls
- [ ] Uses repositories for data access
- [ ] Returns Model or value object

### Controllers
- [ ] Thin (10-15 lines)
- [ ] Uses FormRequest for validation
- [ ] Calls authorize() for authorization
- [ ] Creates DTO from request + route params
- [ ] Calls Action
- [ ] Returns via ApiResponserTrait
- [ ] Under Api/ subfolder

### Policies
- [ ] Authorization logic HERE only
- [ ] Store scoping verified
- [ ] Returns boolean
- [ ] Called by controller

### FormRequests
- [ ] Validation rules defined
- [ ] Enums validated with Rule::in() or Enum rule
- [ ] Messages localized
- [ ] authorize() returns true (auth in controller)

### Resources
- [ ] Transforms model to API response
- [ ] No business logic
- [ ] Consistent structure

### Error Handling
- [ ] Uses ErrorCode enum
- [ ] Custom exceptions extend BaseApiException
- [ ] Messages localized
- [ ] Proper HTTP status codes

### Localization
- [ ] All user-facing messages use __()
- [ ] Messages in lang/en/ and lang/ar/
- [ ] Keys follow convention (domain.message)

### API Responses
- [ ] Uses ApiResponserTrait
- [ ] Returns Resource
- [ ] Proper status/message/data structure

---

## 6. Example: Perfect AI Interaction

### Your Prompt:
```
I need to add a "mark order as shipped" feature.

MANDATORY RULES from ARCHITECTURE.md:
1. No database enums
2. Store scoping on all queries
3. DTO with storeId first
4. Thin controller (10-15 lines)
5. Authorization only in Policy
6. Action has business logic
7. Repository for DB access
8. FormRequest for validation
9. Resource for response
10. Localization for messages

Domain: Order
Endpoint: PATCH /api/v1/admin/stores/{store}/orders/{order}/ship

Steps:
1. Confirm domain structure
2. List files to create
3. Show implementation plan
4. Implement following Golden Path

Do this now.
```

### AI Should Respond:
```
I'll implement the "mark order as shipped" feature following ARCHITECTURE.md rules strictly.

DOMAIN CONFIRMATION:
- Domain: Order
- Subdomain: Admin
- Path: app/Actions/Admin/Order/, app/DTOs/Admin/Order/, etc.

FILES TO CREATE:
1. app/Enums/OrderStatusEnum.php (if not exists, add SHIPPED case)
2. app/DTOs/Admin/Order/MarkOrderAsShippedDTO.php
3. app/Actions/Admin/Order/MarkOrderAsShippedAction.php
4. app/Http/Requests/Admin/Order/MarkOrderAsShippedRequest.php
5. app/Http/Controllers/Api/Admin/Order/AdminOrderController.php (add method)
6. app/Policies/OrderPolicy.php (add ship method)
7. lang/en/order.php (add message)
8. lang/ar/order.php (add Arabic message)

ARCHITECTURE COMPLIANCE:
✓ No database enum (using PHP OrderStatusEnum)
✓ Store scoping in repository
✓ DTO with storeId as first parameter
✓ Controller will be thin
✓ Authorization in OrderPolicy
✓ Business logic in Action
✓ Repository for DB access
✓ Validation in FormRequest
✓ Response via Resource + ApiResponserTrait
✓ Localized messages

IMPLEMENTATION PLAN:
[Shows detailed plan following Golden Path]

Proceed with implementation?
```

---

## 7. AI Training Examples

### Example 1: Catch Database Enum Violation

**Prompt:**
```
Review this migration and fix any ARCHITECTURE.md violations:

Schema::create('orders', function (Blueprint $table) {
    $table->enum('status', ['pending', 'paid', 'shipped']);
});
```

**Expected AI Response:**
```
VIOLATION FOUND: Database enum usage (FORBIDDEN by ARCHITECTURE.md)

FIX:
Schema::create('orders', function (Blueprint $table) {
    $table->string('status');  // Changed from enum()
});

ALSO REQUIRED:
1. Create PHP Enum:
   enum OrderStatusEnum: string {
       case PENDING = 'pending';
       case PAID = 'paid';
       case SHIPPED = 'shipped';
   }

2. Add to Model:
   protected $casts = [
       'status' => OrderStatusEnum::class,
   ];

3. Validation:
   'status' => ['required', new Enum(OrderStatusEnum::class)]
```

---

### Example 2: Catch Missing Store Scoping

**Prompt:**
```
Review this repository method for ARCHITECTURE.md compliance:

public function findById(int $id): ?Order {
    return Order::find($id);
}
```

**Expected AI Response:**
```
CRITICAL VIOLATION: Missing store scoping (MANDATORY by ARCHITECTURE.md)

CURRENT CODE (WRONG):
public function findById(int $id): ?Order {
    return Order::find($id);  // ❌ No store_id check!
}

FIXED CODE:
public function findById(int $id, int $storeId): ?Order {
    return Order::where('store_id', $storeId)
               ->where('id', $id)
               ->first();
}

OR (findOrFail version):
public function findByIdOrFail(int $id, int $storeId): Order {
    return Order::where('store_id', $storeId)
               ->findOrFail($id);
}

RULE: ALL commerce domain queries MUST include store_id constraint.
```

---

### Example 3: Catch Authorization in Action

**Prompt:**
```
Review this Action for ARCHITECTURE.md violations:

class DeleteProductAction {
    public function execute(DeleteProductDTO $dto): bool {
        if (!Auth::user()->can('delete-products')) {
            throw new UnauthorizedException();
        }
        
        $product = Product::where('store_id', $dto->storeId)
                         ->findOrFail($dto->productId);
        return $product->delete();
    }
}
```

**Expected AI Response:**
```
CRITICAL VIOLATIONS FOUND:

1. ❌ Authorization in Action (FORBIDDEN)
   - Line: if (!Auth::user()->can('delete-products'))
   - Rule: Actions MUST NEVER check authorization
   - Fix: Remove auth check, add to Policy

2. ❌ Direct Model access in Action
   - Line: Product::where(...)->findOrFail()
   - Rule: Use Repository for database access
   - Fix: Inject and use ProductRepository

CORRECTED CODE:

// Action (NO authorization logic)
class DeleteProductAction {
    public function __construct(
        private ProductRepository $productRepository,
    ) {}
    
    public function execute(DeleteProductDTO $dto): bool {
        $product = $this->productRepository
            ->findByIdOrFail($dto->productId, $dto->storeId);
        
        return $product->delete();
    }
}

// Controller (authorization HERE)
public function destroy(int $store, int $product): JsonResponse {
    $productModel = Product::where('store_id', $store)
                           ->findOrFail($product);
    
    $this->authorize('delete', $productModel);  // ✅ Auth in controller
    
    $this->deleteProductAction->execute(
        DeleteProductDTO::fromRoute($store, $product)
    );
    
    return $this->success(message: __('product.deleted_successfully'));
}

// Policy (authorization logic)
class ProductPolicy {
    public function delete(User $user, Product $product): bool {
        return $user->can('products.delete') 
            && $user->hasAccessToStore($product->store_id);
    }
}
```

---

## 8. Quick Reference Card for AI Prompts

Copy this into every AI prompt:

```
🔥 MANDATORY ARCHITECTURE RULES 🔥

1. NO database enums → use PHP enums
2. ALL queries MUST include where('store_id', $storeId)
3. DTOs REQUIRED, storeId MUST be first parameter
4. Controllers MUST be thin (10-15 lines)
5. Authorization ONLY in Policies (via $this->authorize())
6. Actions MUST NEVER check auth or call Auth::user()
7. Database access ONLY via Repositories
8. Business logic ONLY in Actions
9. Validation ONLY in FormRequests
10. ALL messages MUST use __() localization
11. Responses MUST use ApiResponserTrait
12. Folder structure: Domain BEFORE type (app/Actions/{Domain}/)
13. Error codes MUST use ErrorCode enum
14. Custom exceptions extend BaseApiException

NO EXCEPTIONS. NO SHORTCUTS. FOLLOW STRICTLY.
```

---

## 9. Enforcement Workflow

### Step 1: Before Asking AI
- [ ] Identify which domain the task belongs to
- [ ] Know which rules apply
- [ ] Have ARCHITECTURE.md open
- [ ] Prepare rule checklist

### Step 2: In Your Prompt
- [ ] Reference ARCHITECTURE.md explicitly
- [ ] List critical rules for this task
- [ ] Demand confirmation before implementation
- [ ] Request architecture compliance check

### Step 3: After AI Responds
- [ ] Review against checklist
- [ ] Check for rule violations
- [ ] Verify folder structure
- [ ] Confirm Golden Path followed

### Step 4: If Violations Found
- [ ] Point out specific rule violated
- [ ] Reference ARCHITECTURE.md section
- [ ] Demand correction
- [ ] Re-verify after fix

---

## 10. Summary

### To Make AI Follow Rules:

1. **Always reference ARCHITECTURE.md in prompts**
2. **List critical rules explicitly**
3. **Use provided prompt templates**
4. **Demand confirmation before implementation**
5. **Review output against checklist**
6. **Reject code that violates rules**
7. **Train AI by pointing out violations**

### Key Success Factors:

- **Be explicit**: Don't assume AI knows the rules
- **Be repetitive**: Mention rules in every prompt
- **Be strict**: Reject any violation immediately
- **Be consistent**: Always enforce, never compromise

---

## 11. Resources

**Primary Authority:**
- `/laratenant-backend/docs/ARCHITECTURE.md` - Supreme law
- `/laratenant-backend/docs/EXECUTION_GOVERNANCE.md` - Execution rules

**Quick References:**
- This document - AI enforcement guide
- Rule checklist above
- Prompt templates above

---

**Remember**: AI is a tool. YOU enforce the rules. Be strict, be consistent, and your codebase will remain clean and maintainable.

---

**Date**: June 7, 2026  
**Authority**: ARCHITECTURE.md  
**Status**: Active enforcement guide
