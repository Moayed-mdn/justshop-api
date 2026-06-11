# Hero Banner Architecture Fix

## Problem

The `AdminHeroBannerController` created by the previous AI violated the project's architecture in multiple critical ways, breaking the established contract defined in `ARCHITECTURE.md`.

## Violations Found

### 1. **Fat Controller** (Most Critical)
- Controller had ~200 lines with business logic directly in methods
- Should be ~10-15 lines per method like `AdminBrandController`

### 2. **Manual DB Operations**
- Used `HeroBanner::create()` and `HeroBannerTranslation::create()` directly in controller
- Should delegate to Repository through Actions

### 3. **No DTOs**
- Used Request objects directly: `$request->input('field')`
- Should use typed DTOs with `fromRequest()` factory

### 4. **Manual Transaction Handling**
- `DB::beginTransaction()` and `DB::commit()` in controller
- Should be in Actions, not Controllers

### 5. **Manual Exception Handling**
- `try/catch` blocks with manual error responses
- Violates centralized error handling doctrine

### 6. **Inconsistent Authorization**
- Used `Gate::authorize()` instead of `$this->authorize()`
- Inconsistent with other admin controllers

### 7. **No Action/Repository Pattern**
- Business logic directly in controller
- No separation of concerns

## Solution Implemented

### Created Missing Architecture Layers

#### 1. **Repository** (`app/Repositories/HeroBanner/HeroBannerRepository.php`)
```php
- list()            // With status and search filtering
- findById()        // Single banner retrieval
- findByIdOrFail()  // With exception
- create()          // With translations (transactional)
- update()          // With translations (transactional)
- delete()          // Soft delete
- restore()         // Restore soft-deleted
```

#### 2. **DTOs** (`app/DTOs/Admin/HeroBanner/`)
```php
- ListHeroBannersDTO     // fromRequest() factory
- ShowHeroBannerDTO      // Simple constructor
- CreateHeroBannerDTO    // fromRequest() factory, storeId first
- UpdateHeroBannerDTO    // fromRequest() factory, storeId first
- DeleteHeroBannerDTO    // Simple constructor
- RestoreHeroBannerDTO   // Simple constructor
```

#### 3. **Actions** (`app/Actions/Admin/HeroBanner/`)
```php
- ListHeroBannersAction   // Delegates to repository
- ShowHeroBannerAction    // Delegates to repository
- CreateHeroBannerAction  // DB::transaction wrapper
- UpdateHeroBannerAction  // DB::transaction wrapper
- DeleteHeroBannerAction  // Delegates to repository
- RestoreHeroBannerAction // Delegates to repository
```

#### 4. **Form Requests** (`app/Http/Requests/Admin/HeroBanner/`)
```php
- CreateHeroBannerRequest // Enum validation, translations
- UpdateHeroBannerRequest // Enum validation, translations
```

#### 5. **Rewritten Controller**
**Before**: 200 lines with business logic
**After**: ~130 lines, thin controller (~10-15 lines per method)

```php
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
```

## Architecture Compliance

The refactored Hero Banner implementation now follows the project's Golden Flow:

```
Request
 → FormRequest (validation)
 → Controller (thin, authorization only)
 → DTO (typed data transfer)
 → Action (business logic)
 → Repository (database access, store-scoped)
 → Resource (transformation)
 → ApiResponserTrait (response formatting)
```

### Key Compliance Points

✅ **Thin Controllers** - Each method ~10-15 lines
✅ **DTOs Mandatory** - All actions receive DTOs
✅ **Repository Pattern** - Only database access layer
✅ **Store Scoping** - All queries include `store_id`
✅ **Centralized Error Handling** - No manual try/catch
✅ **Policy Authorization** - Via `$this->authorize()`
✅ **Action Delegation** - Business logic isolated in Actions
✅ **Transaction Handling** - In Actions, not Controllers
✅ **Consistent with Existing Code** - Matches Brand/Category/Tag patterns

## Documentation Added

Added section **16.18 Hero Banner Management** to `docs/ARCHITECTURE.md`:
- Complete architecture overview
- Repository/Action/DTO/Controller structure
- Data model and features
- API endpoints
- Frontend integration notes

## Files Modified

### Created (13 files):
1. `app/Repositories/HeroBanner/HeroBannerRepository.php`
2. `app/DTOs/Admin/HeroBanner/ListHeroBannersDTO.php`
3. `app/DTOs/Admin/HeroBanner/ShowHeroBannerDTO.php`
4. `app/DTOs/Admin/HeroBanner/CreateHeroBannerDTO.php`
5. `app/DTOs/Admin/HeroBanner/UpdateHeroBannerDTO.php`
6. `app/DTOs/Admin/HeroBanner/DeleteHeroBannerDTO.php`
7. `app/DTOs/Admin/HeroBanner/RestoreHeroBannerDTO.php`
8. `app/Actions/Admin/HeroBanner/ListHeroBannersAction.php`
9. `app/Actions/Admin/HeroBanner/ShowHeroBannerAction.php`
10. `app/Actions/Admin/HeroBanner/CreateHeroBannerAction.php`
11. `app/Actions/Admin/HeroBanner/UpdateHeroBannerAction.php`
12. `app/Actions/Admin/HeroBanner/DeleteHeroBannerAction.php`
13. `app/Actions/Admin/HeroBanner/RestoreHeroBannerAction.php`
14. `app/Http/Requests/Admin/HeroBanner/CreateHeroBannerRequest.php`
15. `app/Http/Requests/Admin/HeroBanner/UpdateHeroBannerRequest.php`

### Rewritten (1 file):
- `app/Http/Controllers/Api/Merchant/AdminHeroBannerController.php`
  - From 200 lines (fat) to 130 lines (thin)
  - Removed all business logic
  - Added proper Action/DTO delegation

### Updated (1 file):
- `docs/ARCHITECTURE.md`
  - Added section 16.18 documenting Hero Banner architecture

## Result

The Hero Banner feature is now:
- ✅ Fully architecture-compliant
- ✅ Consistent with Brand/Category/Tag implementations
- ✅ Properly documented in ARCHITECTURE.md
- ✅ Maintainable and testable
- ✅ Following all project conventions

## Lessons Learned

When implementing new features in this codebase:
1. **Always check existing implementations** (Brand, Category, Tag) for patterns
2. **Never put business logic in controllers** - use Actions
3. **Always create DTOs** - no direct Request usage in business logic
4. **Always use Repository** - no direct Model access
5. **Document in ARCHITECTURE.md** - features must be documented
6. **Follow the Golden Flow** - Request → FormRequest → Controller → DTO → Action → Repository → Resource → Response
