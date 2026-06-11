# Before vs After: Hero Banner Architecture Fix

## Visual Comparison

### BEFORE: Broken Architecture ❌

```
┌─────────────────────────────────────────────┐
│   AdminHeroBannerController (FAT)          │
│   • 200 lines of mixed concerns            │
│   • Business logic in controller           │
│   • Direct Model access                    │
│   • Manual transactions                    │
│   • Manual error handling (try/catch)      │
│   • No DTOs                                │
│   • No Actions                             │
│   • No Repository                          │
├─────────────────────────────────────────────┤
│   Direct database calls:                   │
│   HeroBanner::create([...])               │
│   HeroBannerTranslation::create([...])    │
└─────────────────────────────────────────────┘

Missing Layers:
❌ No Repository
❌ No Actions  
❌ No DTOs
❌ No proper separation of concerns
```

### AFTER: Clean Architecture ✅

```
┌─────────────────────────────────────────────┐
│   Request                                   │
└──────────────┬──────────────────────────────┘
               ↓
┌─────────────────────────────────────────────┐
│   FormRequest (Validation)                  │
│   • CreateHeroBannerRequest                 │
│   • UpdateHeroBannerRequest                 │
└──────────────┬──────────────────────────────┘
               ↓
┌─────────────────────────────────────────────┐
│   Controller (Thin - Auth & Delegation)     │
│   • AdminHeroBannerController               │
│   • 10-15 lines per method                  │
│   • $this->authorize() only                 │
│   • Action delegation only                  │
└──────────────┬──────────────────────────────┘
               ↓
┌─────────────────────────────────────────────┐
│   DTO (Typed Data Transfer)                 │
│   • CreateHeroBannerDTO::fromRequest()      │
│   • storeId as first parameter              │
│   • Strictly typed, immutable               │
└──────────────┬──────────────────────────────┘
               ↓
┌─────────────────────────────────────────────┐
│   Action (Business Logic)                   │
│   • CreateHeroBannerAction                  │
│   • DB::transaction() wrapper               │
│   • Orchestrates Repository calls           │
└──────────────┬──────────────────────────────┘
               ↓
┌─────────────────────────────────────────────┐
│   Repository (Database Access)              │
│   • HeroBannerRepository                    │
│   • Store-scoped queries                    │
│   • Soft delete support                     │
│   • Translation handling                    │
└──────────────┬──────────────────────────────┘
               ↓
┌─────────────────────────────────────────────┐
│   Resource (Transformation)                 │
│   • AdminHeroBannerResource                 │
└──────────────┬──────────────────────────────┘
               ↓
┌─────────────────────────────────────────────┐
│   ApiResponserTrait (Response)              │
│   • Standardized response format            │
└─────────────────────────────────────────────┘
```

## Code Comparison: Create Method

### BEFORE (Broken) ❌

```php
/**
 * AdminHeroBannerController::store() - 50+ lines
 */
public function store(StoreHeroBannerRequest $request, int $storeId): JsonResponse
{
    Gate::authorize('create', [HeroBanner::class, $storeId]);

    try {
        DB::beginTransaction();

        // Direct Model access - WRONG!
        $banner = HeroBanner::create([
            'store_id' => $storeId,
            'cat_url' => $request->input('cat_url'),
            'position' => $request->input('position'),
            'visual_type' => $request->input('visual_type'),
            'image_path' => $request->input('image_path'),
            'gradient_from' => $request->input('gradient_from'),
            'gradient_to' => $request->input('gradient_to'),
            'link_url' => $request->input('link_url'),
            'link_text' => $request->input('link_text'),
            'link_target' => $request->input('link_target'),
            'is_active' => $request->input('is_active', true),
            'starts_at' => $request->input('starts_at'),
            'ends_at' => $request->input('ends_at'),
        ]);

        // Direct translation creation - WRONG!
        foreach ($request->input('translations', []) as $translationData) {
            HeroBannerTranslation::create([
                'hero_banner_id' => $banner->id,
                'locale' => $translationData['locale'],
                'title' => $translationData['title'],
                'subtitle' => $translationData['subtitle'] ?? null,
                'cta_text' => $translationData['cta_text'],
            ]);
        }

        DB::commit();

        $banner->load('translations');

        return $this->success(
            new AdminHeroBannerResource($banner),
            'Hero banner created successfully',
            201
        );
    } catch (\Exception $e) {
        DB::rollBack();
        return $this->error('Failed to create hero banner: ' . $e->getMessage(), 500);
    }
}

// Problems:
// ❌ 50+ lines (should be ~10-15)
// ❌ Business logic in controller
// ❌ Direct Model access (should use Repository)
// ❌ No DTO (uses Request directly)
// ❌ Manual transaction handling
// ❌ Manual error handling (try/catch)
// ❌ Gate::authorize() instead of $this->authorize()
```

### AFTER (Clean) ✅

```php
/**
 * AdminHeroBannerController::store() - 12 lines
 */
public function store(
    CreateHeroBannerRequest $request,
    int $store,
    CreateHeroBannerAction $action,
): JsonResponse {
    $this->authorize('create', [HeroBanner::class, $this->currentStore()]);

    $result = $action->execute(
        dto: CreateHeroBannerDTO::fromRequest($request, $store),
    );

    return $this->success(
        data:       new AdminHeroBannerResource($result),
        message:    __('hero_banner.created'),
        statusCode: 201,
    );
}

// Supporting layers:

/**
 * CreateHeroBannerDTO - Data Transfer
 */
class CreateHeroBannerDTO
{
    public function __construct(
        public readonly int $storeId,  // ← First parameter (required)
        public readonly string $catUrl,
        public readonly int $position,
        // ... all other fields typed
    ) {}

    public static function fromRequest(
        CreateHeroBannerRequest $request,
        int $storeId,
    ): self {
        return new self(
            storeId: $storeId,
            catUrl: $request->string('cat_url')->toString(),
            position: $request->integer('position'),
            // ... all other fields
        );
    }
}

/**
 * CreateHeroBannerAction - Business Logic
 */
class CreateHeroBannerAction
{
    public function __construct(
        private HeroBannerRepository $heroBannerRepository,
    ) {}

    public function execute(CreateHeroBannerDTO $dto): HeroBanner
    {
        return DB::transaction(function () use ($dto) {
            return $this->heroBannerRepository->create(
                storeId: $dto->storeId,
                catUrl: $dto->catUrl,
                // ... all other fields
                translations: $dto->translations,
            );
        });
    }
}

/**
 * HeroBannerRepository - Database Access
 */
class HeroBannerRepository extends BaseRepository
{
    public function create(
        int $storeId,
        string $catUrl,
        // ... all parameters
        array $translations,
    ): HeroBanner {
        $banner = HeroBanner::create([
            'store_id' => $storeId,
            'cat_url' => $catUrl,
            // ... all fields
        ]);

        foreach ($translations as $translation) {
            $banner->translations()->create([...]);
        }

        return $banner->load('translations');
    }
}

// Benefits:
// ✅ 12 lines in controller (thin)
// ✅ Clean separation of concerns
// ✅ Repository handles DB access
// ✅ DTO provides type safety
// ✅ Action contains business logic
// ✅ Centralized error handling
// ✅ Consistent with project patterns
```

## Layer Breakdown

### What Goes Where?

| Layer | Responsibility | Example |
|-------|---------------|---------|
| **FormRequest** | Input validation only | `'position' => ['required', 'integer', 'min:0']` |
| **Controller** | Authorization + delegation (10-15 lines) | `$this->authorize()` + `$action->execute()` |
| **DTO** | Typed data transfer | `public readonly int $storeId` |
| **Action** | Business logic + orchestration | `DB::transaction()`, call repository |
| **Repository** | Database queries + mutations | `HeroBanner::create()`, `->where('store_id')` |
| **Resource** | Response transformation | JSON structure mapping |

## File Count Comparison

### BEFORE ❌
```
app/Http/Controllers/Api/Merchant/AdminHeroBannerController.php (FAT)

Total: 1 file with everything mixed together
```

### AFTER ✅
```
app/Http/Controllers/Api/Merchant/AdminHeroBannerController.php (THIN)
app/Repositories/HeroBanner/HeroBannerRepository.php
app/Actions/Admin/HeroBanner/
  ├── ListHeroBannersAction.php
  ├── ShowHeroBannerAction.php
  ├── CreateHeroBannerAction.php
  ├── UpdateHeroBannerAction.php
  ├── DeleteHeroBannerAction.php
  └── RestoreHeroBannerAction.php
app/DTOs/Admin/HeroBanner/
  ├── ListHeroBannersDTO.php
  ├── ShowHeroBannerDTO.php
  ├── CreateHeroBannerDTO.php
  ├── UpdateHeroBannerDTO.php
  ├── DeleteHeroBannerDTO.php
  └── RestoreHeroBannerDTO.php
app/Http/Requests/Admin/HeroBanner/
  ├── CreateHeroBannerRequest.php
  └── UpdateHeroBannerRequest.php

Total: 16 files with proper separation of concerns
```

## Architecture Compliance

### BEFORE: 0/8 Rules Followed ❌

- ❌ Thin Controllers
- ❌ DTOs Mandatory
- ❌ Repository Pattern
- ❌ Store Scoping (was done manually)
- ❌ Action Delegation
- ❌ Centralized Error Handling
- ❌ Policy Authorization (wrong method)
- ❌ Consistent Patterns

### AFTER: 8/8 Rules Followed ✅

- ✅ Thin Controllers (10-15 lines per method)
- ✅ DTOs Mandatory (all actions receive DTOs)
- ✅ Repository Pattern (only DB access layer)
- ✅ Store Scoping (via Repository)
- ✅ Action Delegation (business logic isolated)
- ✅ Centralized Error Handling (no try/catch)
- ✅ Policy Authorization ($this->authorize())
- ✅ Consistent Patterns (matches Brand/Category/Tag)

## Why This Matters

### Maintainability
**Before**: All logic in one place → hard to test, hard to change
**After**: Each layer has one job → easy to test, easy to change

### Testability
**Before**: Must mock entire controller with DB, transactions, etc.
**After**: Test each layer independently (unit tests for Actions, integration tests for Repository)

### Consistency
**Before**: Different from Brand, Category, Tag implementations
**After**: Exactly matches existing patterns → predictable codebase

### Scalability
**Before**: Adding features means making controller even fatter
**After**: Adding features means adding new Actions/DTOs → controller stays thin

## Documentation

### BEFORE ❌
- No mention of Hero Banners in ARCHITECTURE.md
- Feature exists but undocumented
- Violations not visible to other developers

### AFTER ✅
- Section 16.18 in ARCHITECTURE.md documents:
  - Architecture compliance
  - All layers (Repository/Action/DTO/Controller)
  - API endpoints
  - Data model
  - Frontend integration

## Conclusion

The "broken ARCHITECTURE.md" was actually:
1. ❌ A controller that violated ARCHITECTURE.md rules
2. ❌ A feature that wasn't documented in ARCHITECTURE.md
3. ✅ Fixed by implementing proper architecture layers
4. ✅ Fixed by documenting the feature properly

**Result**: Hero Banner feature is now fully architecture-compliant, properly documented, and consistent with the rest of the codebase.
