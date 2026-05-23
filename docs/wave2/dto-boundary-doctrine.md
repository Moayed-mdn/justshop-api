# DTO Boundary Doctrine

**Version:** 1.0  
**Status:** APPROVED  
**Wave:** 2  
**Date:** 2026-05-23

---

## Purpose

This document defines DTO classifications, allowed boundaries, forbidden boundaries, serialization rules, and migration-safe DTO practices. This is NOT a mass DTO rewrite - it is boundary clarification ONLY.

---

## DTO Classification Matrix

### 1. Transport DTOs
**Purpose:** Carry data from HTTP request to Action  
**Lifecycle:** Request → DTO → Action  
**Serialization:** Not required  
**Coupling:** May couple to Request object

**Characteristics:**
- Created from `FormRequest` via `fromRequest()` factory
- Contains validated, typed data
- May include route parameters
- May include authenticated user context
- Destroyed after Action execution

**Example:**
```php
class CreateProductDTO
{
    public function __construct(
        public int $storeId,        // From route
        public string $name,         // From request body
        public int $userId,          // From auth context
    ) {}

    public static function fromRequest(CreateProductRequest $request, int $store): self
    {
        return new self(
            storeId: $store,
            name: $request->string('name'),
            userId: $request->user()->id,
        );
    }
}
```

**Allowed Coupling:**
- ✓ Request object (in factory method only)
- ✓ Route parameters
- ✓ Auth context
- ✓ Primitive types

**Forbidden Coupling:**
- ✗ Eloquent models as properties
- ✗ Session access
- ✗ Database queries
- ✗ Business logic

---

### 2. Orchestration DTOs
**Purpose:** Carry data between Actions/Services  
**Lifecycle:** Action → DTO → Action/Service  
**Serialization:** Not required (unless async)  
**Coupling:** Should be decoupled from Request

**Characteristics:**
- Created programmatically (not from Request)
- Contains typed, validated data
- May be nested or composed
- Used for internal orchestration
- Destroyed after use

**Example:**
```php
class ProcessPaymentDTO
{
    public function __construct(
        public int $orderId,
        public int $amount,
        public string $currency,
        public string $paymentMethodId,
    ) {}
}
```

**Allowed Coupling:**
- ✓ Primitive types
- ✓ Value objects
- ✓ Other DTOs (composition)
- ✓ Enums

**Forbidden Coupling:**
- ✗ Request object
- ✗ Eloquent models as properties
- ✗ Session access
- ✗ Database queries

---

### 3. Domain Contracts
**Purpose:** Define stable interfaces between bounded contexts  
**Lifecycle:** Long-lived, version-controlled  
**Serialization:** Required  
**Coupling:** Strictly decoupled

**Characteristics:**
- Immutable structure
- Version-controlled
- Serialization-safe
- No framework dependencies
- Used for async communication

**Example:**
```php
class OrderCreatedEvent
{
    public function __construct(
        public readonly int $orderId,
        public readonly int $storeId,
        public readonly int $userId,
        public readonly int $totalAmount,
        public readonly string $currency,
        public readonly string $createdAt,
    ) {}

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'store_id' => $this->storeId,
            'user_id' => $this->userId,
            'total_amount' => $this->totalAmount,
            'currency' => $this->currency,
            'created_at' => $this->createdAt,
        ];
    }
}
```

**Allowed Coupling:**
- ✓ Primitive types only
- ✓ Readonly properties
- ✓ Explicit serialization methods

**Forbidden Coupling:**
- ✗ Any framework dependencies
- ✗ Eloquent models
- ✗ Request objects
- ✗ Mutable state

---

### 4. Serialization-Safe Contracts
**Purpose:** Data structures for caching, queuing, or external APIs  
**Lifecycle:** May be persisted or transmitted  
**Serialization:** Required  
**Coupling:** Strictly decoupled

**Characteristics:**
- JSON-serializable
- No object references
- No closures
- Primitive types only
- Explicit versioning

**Example:**
```php
class CachedProductData
{
    public function __construct(
        public int $id,
        public string $name,
        public int $price,
        public bool $isActive,
        public array $tags,
    ) {}

    public function toJson(): string
    {
        return json_encode([
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'is_active' => $this->isActive,
            'tags' => $this->tags,
        ]);
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        return new self(
            id: $data['id'],
            name: $data['name'],
            price: $data['price'],
            isActive: $data['is_active'],
            tags: $data['tags'],
        );
    }
}
```

**Allowed Coupling:**
- ✓ Primitive types only
- ✓ Arrays of primitives
- ✓ Explicit serialization/deserialization

**Forbidden Coupling:**
- ✗ Object references
- ✗ Closures
- ✗ Resources
- ✗ Framework dependencies

---

### 5. Compatibility DTOs
**Purpose:** Bridge old and new systems during migration  
**Lifecycle:** Temporary (removed after migration)  
**Serialization:** May be required  
**Coupling:** May couple to legacy systems

**Characteristics:**
- Temporary existence
- Explicit migration flag
- Documented removal date
- May contain adapters
- Preserves backward compatibility

**Example:**
```php
/**
 * Wave 2 Compatibility DTO
 * 
 * Bridges legacy bootstrap payload structure with new resolver architecture.
 * 
 * @deprecated Remove after Wave 3 bootstrap decomposition (target: 2026-Q3)
 */
class LegacyBootstrapDTO
{
    public function __construct(
        public array $legacyPayload,
        public array $newPayload,
        public bool $useLegacy = true,
    ) {}
}
```

**Allowed Coupling:**
- ✓ Legacy system structures (temporarily)
- ✓ Compatibility flags
- ✓ Adapter patterns

**Forbidden Coupling:**
- ✗ New business logic
- ✗ Permanent dependencies

---

## Migration-Blocking DTO List

### Current Blockers (Wave 2)

None identified. Current DTO patterns are migration-safe for Wave 3.

### Potential Future Blockers

1. **Request-Coupled Event DTOs**
   - **Risk:** If events are created directly from Request objects
   - **Mitigation:** Use Transport DTOs as intermediary

2. **Model-Carrying Cache DTOs**
   - **Risk:** Eloquent models in cached structures
   - **Mitigation:** Use Serialization-Safe Contracts

3. **Session-Dependent DTOs**
   - **Risk:** DTOs that access session state
   - **Mitigation:** Pass session data explicitly as constructor params

---

## Allowed Coupling Rules

### Rule 1: Transport DTOs May Couple to Request
**Rationale:** Transport DTOs exist to extract data from requests  
**Scope:** Factory method only (`fromRequest()`)  
**Constraint:** No Request reference stored as property

```php
// ✓ ALLOWED
public static function fromRequest(CreateProductRequest $request, int $store): self
{
    return new self(
        storeId: $store,
        name: $request->string('name'),
    );
}

// ✗ FORBIDDEN
public function __construct(
    public CreateProductRequest $request, // NO!
) {}
```

---

### Rule 2: Orchestration DTOs May Compose Other DTOs
**Rationale:** Complex workflows need structured data  
**Scope:** Constructor parameters  
**Constraint:** No circular dependencies

```php
// ✓ ALLOWED
class CheckoutDTO
{
    public function __construct(
        public CartDTO $cart,
        public AddressDTO $shippingAddress,
        public PaymentMethodDTO $paymentMethod,
    ) {}
}
```

---

### Rule 3: Domain Contracts Must Be Primitive-Only
**Rationale:** Serialization safety and version stability  
**Scope:** All properties  
**Constraint:** No object references except other primitive-only DTOs

```php
// ✓ ALLOWED
public function __construct(
    public int $orderId,
    public string $status,
    public array $items, // Array of primitives
) {}

// ✗ FORBIDDEN
public function __construct(
    public Order $order, // NO!
) {}
```

---

## Forbidden Coupling Rules

### Rule 1: No Eloquent Models as DTO Properties
**Rationale:** Breaks serialization, creates hidden dependencies  
**Violation Level:** CRITICAL

```php
// ✗ FORBIDDEN
class CreateOrderDTO
{
    public function __construct(
        public User $user,        // NO!
        public Cart $cart,        // NO!
    ) {}
}

// ✓ ALLOWED
class CreateOrderDTO
{
    public function __construct(
        public int $userId,       // YES
        public int $cartId,       // YES
    ) {}
}
```

---

### Rule 2: No Request/Session Access in DTO Methods
**Rationale:** Creates hidden coupling, breaks testability  
**Violation Level:** HIGH

```php
// ✗ FORBIDDEN
class CreateProductDTO
{
    public function getStoreId(): int
    {
        return request()->route('store'); // NO!
    }
}

// ✓ ALLOWED
class CreateProductDTO
{
    public function __construct(
        public int $storeId, // Passed explicitly
    ) {}
}
```

---

### Rule 3: No Business Logic in DTOs
**Rationale:** DTOs are data carriers, not behavior containers  
**Violation Level:** MEDIUM

```php
// ✗ FORBIDDEN
class CreateOrderDTO
{
    public function calculateTotal(): int
    {
        // Business logic doesn't belong here!
        return array_sum($this->items);
    }
}

// ✓ ALLOWED
class CreateOrderDTO
{
    public function __construct(
        public array $items,
        public int $calculatedTotal, // Calculated elsewhere
    ) {}
}
```

---

### Rule 4: No Database Queries in DTOs
**Rationale:** DTOs should be passive data structures  
**Violation Level:** CRITICAL

```php
// ✗ FORBIDDEN
class CreateProductDTO
{
    public function getStore(): Store
    {
        return Store::find($this->storeId); // NO!
    }
}

// ✓ ALLOWED
// Query in Action/Service, pass result to DTO if needed
```

---

## Serialization Safety Rules

### Rule 1: Serializable DTOs Must Implement Explicit Methods
**Requirement:** `toArray()` and `fromArray()` methods

```php
class SerializableDTO
{
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
        );
    }
}
```

---

### Rule 2: No Closures in Serializable DTOs
**Rationale:** Closures cannot be serialized

```php
// ✗ FORBIDDEN
class EventDTO
{
    public function __construct(
        public Closure $callback, // NO!
    ) {}
}
```

---

### Rule 3: No Resource Handles in Serializable DTOs
**Rationale:** Resources cannot be serialized

```php
// ✗ FORBIDDEN
class FileDTO
{
    public function __construct(
        public $fileHandle, // NO!
    ) {}
}
```

---

## Migration-Safe DTO Practices

### Practice 1: Use Readonly Properties for Immutability
```php
class ImmutableDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
```

---

### Practice 2: Use Named Constructors for Clarity
```php
class ProductDTO
{
    private function __construct(
        public int $id,
        public string $name,
    ) {}

    public static function fromRequest(CreateProductRequest $request): self
    {
        return new self(
            id: $request->integer('id'),
            name: $request->string('name'),
        );
    }

    public static function fromModel(Product $product): self
    {
        return new self(
            id: $product->id,
            name: $product->name,
        );
    }
}
```

---

### Practice 3: Use Type Hints Strictly
```php
class StrictDTO
{
    public function __construct(
        public int $id,              // Not mixed
        public string $name,          // Not ?string unless nullable
        public array $tags,           // Document array shape in docblock
    ) {}
}
```

---

### Practice 4: Document Array Shapes
```php
class DocumentedDTO
{
    /**
     * @param array<int, string> $tags Array of tag names
     */
    public function __construct(
        public array $tags,
    ) {}
}
```

---

## Verification Checklist

### For Transport DTOs
- [ ] Factory method accepts Request object
- [ ] No Request stored as property
- [ ] All properties are typed
- [ ] Route parameters extracted explicitly
- [ ] Auth context extracted explicitly

### For Orchestration DTOs
- [ ] No Request coupling
- [ ] All properties are typed
- [ ] No circular dependencies
- [ ] No business logic

### For Domain Contracts
- [ ] All properties are primitives
- [ ] Readonly properties used
- [ ] Serialization methods implemented
- [ ] No framework dependencies
- [ ] Version documented

### For Serialization-Safe Contracts
- [ ] JSON-serializable
- [ ] No object references
- [ ] No closures
- [ ] No resources
- [ ] Explicit serialization/deserialization

---

## Governance Compliance

### ARCHITECTURE.md Compliance
- ✓ DTOs are mandatory for Actions
- ✓ DTOs are strictly typed
- ✓ DTOs are immutable
- ✓ No arrays in business logic

### EXECUTION_GOVERNANCE.md Compliance
- ✓ No mass DTO conversion
- ✓ Compatibility-first approach
- ✓ Explicit boundary definitions
- ✓ Migration-safe practices documented

---

## Conclusion

This doctrine establishes clear DTO boundaries without requiring mass conversion. Current DTO patterns are migration-safe for Wave 3. Future DTO work should follow these classifications and rules to maintain architectural consistency.

**Status:** APPROVED for Wave 2  
**Next Review:** Before async adoption (Wave 4+)
