# 🚀 AI Quick Start - Practical Example

**How to use the AI Rules Enforcement System in practice**  
**Time to learn**: 5 minutes  
**Time to apply**: Immediate

---

## 📋 Scenario

You want AI to help you implement a new feature: "Add discount code to orders"

---

## ❌ WRONG WAY (Without Rules)

### Your Prompt:
```
Add a discount code feature to orders
```

### AI Response (BAD):
```php
// Creates this mess:
Schema::create('orders', function (Blueprint $table) {
    $table->enum('status', ['pending', 'paid']);  // ❌ Database enum!
});

// Fat controller (80 lines)
public function apply(Request $request) {
    // 80 lines of business logic in controller ❌
    if (!Auth::user()->can('apply-discount')) {  // ❌ Auth in controller
        // ...
    }
}

// Direct model access
$order = Order::find($id);  // ❌ No store scoping!
```

**Result**: Breaks ALL your architecture rules! ❌

---

## ✅ RIGHT WAY (With Rules Enforcement)

### Step 1: Copy the Quick Reference Card

From `AI_RULES_ENFORCEMENT_SYSTEM.md`, copy this:

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

### Step 2: Use the Template

Your proper prompt:

```
I need to add a discount code feature to orders.

🔥 MANDATORY RULES (from ARCHITECTURE.md):

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
12. Folder structure: Domain BEFORE type
13. Error codes MUST use ErrorCode enum

FEATURE DETAILS:
- Domain: Order
- Endpoint: POST /api/v1/merchant/stores/{store}/orders/{order}/apply-discount
- Requirements:
  • Validate discount code exists and is active
  • Check code is valid for this store
  • Apply discount percentage to order
  • Update order total
  • Record discount code used

PROCESS:
1. FIRST: Confirm domain structure and list files to create
2. THEN: Show implementation plan
3. WAIT for my approval
4. THEN: Implement following Golden Path strictly

Begin now.
```

### Step 3: AI Response (GOOD)

AI will now respond correctly:

```
I'll implement the discount code feature following ARCHITECTURE.md rules strictly.

DOMAIN CONFIRMATION:
- Domain: Order
- Subdomain: Admin
- Path: app/Actions/Admin/Order/, app/DTOs/Admin/Order/

FILES TO CREATE:
1. app/Enums/DiscountTypeEnum.php (if not exists)
2. app/DTOs/Admin/Order/ApplyDiscountDTO.php
3. app/Actions/Admin/Order/ApplyDiscountToOrderAction.php
4. app/Http/Requests/Admin/Order/ApplyDiscountRequest.php
5. app/Http/Controllers/Api/Admin/Order/AdminOrderController.php (add method)
6. app/Repositories/Order/OrderRepository.php (update if needed)
7. app/Repositories/Discount/DiscountCodeRepository.php (new)
8. app/Policies/OrderPolicy.php (add applyDiscount method)
9. lang/en/order.php (add messages)
10. lang/ar/order.php (add Arabic messages)

ARCHITECTURE COMPLIANCE CHECK:
✓ No database enum (using PHP DiscountTypeEnum)
✓ Store scoping in all queries
✓ DTO with storeId as first parameter
✓ Controller will be thin (10-15 lines)
✓ Authorization in OrderPolicy only
✓ Business logic in Action only
✓ Database access via Repository
✓ Validation in FormRequest
✓ Response via Resource + ApiResponserTrait
✓ Localized messages via __()
✓ Domain-first folder structure

IMPLEMENTATION PLAN:

1. DTO Structure:
```php
public function __construct(
    public int $storeId,         // ✅ First parameter
    public int $orderId,
    public string $discountCode,
) {}
```

2. Controller (THIN):
```php
public function applyDiscount(
    ApplyDiscountRequest $request,
    int $store,
    int $order
): JsonResponse {
    $this->authorize('applyDiscount', Order::find($order));  // ✅ Auth here
    
    $order = $this->applyDiscountAction->execute(
        ApplyDiscountDTO::fromRequest($request, $store, $order)
    );
    
    return $this->success(
        new OrderResource($order),
        __('order.discount_applied')
    );
}
```

3. Action (Business Logic):
```php
public function execute(ApplyDiscountDTO $dto): Order
{
    return DB::transaction(function () use ($dto) {
        // Get order with store scoping ✅
        $order = $this->orderRepository
            ->findByIdOrFail($dto->orderId, $dto->storeId);
        
        // Validate discount code
        $discount = $this->discountRepository
            ->findActiveByCode($dto->discountCode, $dto->storeId);  // ✅ Store scoped
        
        if (!$discount) {
            throw new InvalidDiscountException(
                __('order.invalid_discount_code')  // ✅ Localized
            );
        }
        
        // Apply discount logic
        $discountAmount = $order->total * ($discount->percentage / 100);
        $order->discount_amount = $discountAmount;
        $order->final_total = $order->total - $discountAmount;
        $order->discount_code_id = $discount->id;
        $order->save();
        
        return $order;
    });
}
```

Proceed with implementation?
```

**Result**: Perfect architecture compliance! ✅

---

## 📊 Comparison

| Aspect | Without Rules | With Rules |
|--------|--------------|------------|
| **Database Enum** | ❌ Used | ✅ PHP Enum |
| **Store Scoping** | ❌ Missing | ✅ All queries |
| **Authorization** | ❌ In Action | ✅ In Policy |
| **Controller** | ❌ Fat (80 lines) | ✅ Thin (10 lines) |
| **DTO Structure** | ❌ Wrong order | ✅ storeId first |
| **Localization** | ❌ Hardcoded strings | ✅ __() function |
| **Folder Structure** | ❌ Flat | ✅ Domain-first |
| **Code Quality** | ❌ Broken | ✅ Perfect |

---

## 🎯 Key Takeaways

### DO This:
1. ✅ **Always** paste the rules card into your prompt
2. ✅ **Reference** ARCHITECTURE.md explicitly
3. ✅ **Demand** confirmation before implementation
4. ✅ **Verify** output against checklist
5. ✅ **Reject** any violation immediately

### DON'T Do This:
1. ❌ Assume AI knows your rules
2. ❌ Skip rule reference
3. ❌ Accept code without verification
4. ❌ Let violations slide "just this once"
5. ❌ Forget to check store scoping

---

## 📝 Copy-Paste Template (Use This Every Time)

```
I need to [DESCRIBE YOUR TASK].

🔥 MANDATORY RULES (from ARCHITECTURE.md):
[PASTE THE 13 RULES FROM AI_RULES_ENFORCEMENT_SYSTEM.md]

TASK DETAILS:
- Domain: [YOUR DOMAIN]
- Endpoint: [YOUR ENDPOINT]
- Requirements:
  • [REQUIREMENT 1]
  • [REQUIREMENT 2]
  • [REQUIREMENT 3]

PROCESS:
1. FIRST: Confirm domain structure and files to create
2. THEN: Show implementation plan
3. WAIT for my approval
4. THEN: Implement following Golden Path

Begin now.
```

---

## ✅ Checklist After AI Responds

Before accepting AI's code, verify:

- [ ] No database enum() used
- [ ] All queries have where('store_id', $storeId)
- [ ] DTO has storeId as first parameter
- [ ] Controller is thin (10-15 lines)
- [ ] Authorization via $this->authorize() in controller
- [ ] No Auth::user() in Action
- [ ] Repository used for database access
- [ ] Business logic in Action only
- [ ] Validation in FormRequest
- [ ] Resource for API response
- [ ] ApiResponserTrait used
- [ ] All messages use __()
- [ ] Files in correct domain folders
- [ ] ErrorCode enum for errors

**If any check fails**: Reject and ask AI to fix!

---

## 🚀 Practice Exercise

Try it now! Ask AI to implement:

**Feature**: "Add a note to an order"

**Your prompt** (fill in the template above):
- Domain: Order
- Endpoint: POST /api/v1/merchant/stores/{store}/orders/{order}/notes
- Include all 13 rules
- Demand confirmation first

**Expected result**: AI will create architecture-compliant code!

---

## 💡 Pro Tips

1. **Keep the rules card handy** - Bookmark AI_RULES_ENFORCEMENT_SYSTEM.md
2. **Use the template every time** - Consistency is key
3. **Be strict** - No exceptions to rules
4. **Train AI** - Point out violations when found
5. **Share with team** - Everyone should use this approach

---

## 📚 Resources

- **Full Guide**: [AI_RULES_ENFORCEMENT_SYSTEM.md](./AI_RULES_ENFORCEMENT_SYSTEM.md)
- **Architecture Rules**: [ARCHITECTURE.md](./ARCHITECTURE.md)
- **Entry Point**: [00-START-HERE.md](./00-START-HERE.md)
- **Documentation Index**: [README.md](./README.md)

---

## 🎉 You're Ready!

Now you know how to make AI follow your architecture rules strictly!

**Next**: Try the practice exercise above, then use this approach for all AI interactions.

---

**Remember**: The key is to **always reference the rules** and **verify the output**. AI is powerful, but YOU enforce the standards! 💪

---

**Created**: June 7, 2026  
**Purpose**: Practical AI collaboration guide  
**Time to master**: 5 minutes reading + 1 practice exercise
