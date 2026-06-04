# Guard Split Feasibility

## Objective

Evaluate whether `TransitionalGuardResolver` should be split into **separate guard resolver classes** (one per domain: `customer`, `merchant`, `admin`) instead of the current single-class-plus-internal-map approach.

## Current Architecture

```
TransitionalGuardResolver (single class)
  ├── guardMap = ['customer' => TenantGuard, 'merchant' => TenantGuard]
  └── resolve(): iterates map → matches → resolves
```

## Proposed Architecture

```
GuardResolverInterface
  ├── CustomerGuardResolver (customer domain)
  ├── MerchantGuardResolver (merchant domain)
  └── BoundaryGuardResolver (admin domain)
```

Each resolver would:
1. Accept only requests matching its domain
2. Contain domain-specific resolution logic
3. Be individually testable
4. Be registered as separate services in the container

## Benefits

| Benefit | Impact |
|---------|--------|
| **Single Responsibility** | Each resolver knows only its domain |
| **Testability** | Domain-specific edge cases tested in isolation |
| **Extensibility** | New domains add a class, not a branch in a switch |
| **Removes `$authDomain` null-safety issue** | Each resolver knows its domain is non-null |
| **Open/Closed Principle** | New domains = new files, no modification of existing resolvers |

## Drawbacks

| Drawback | Impact |
|----------|--------|
| **More files** | 3 resolvers + interface vs 1 class |
| **Resolution dispatch** | Need a factory or router to pick the right resolver |
| **Over-engineering risk** | If all guards do the same thing, a single class with config is simpler |
| **Current parity**: `TenantGuard` is used for both customer and merchant | Splitting resolvers does not change this; both would still delegate to `TenantGuard` |

## Key Finding

**`TenantGuard` is already shared** between `customer` and `merchant`. The split at the **resolver** level would not change that. The real differentiation would need to happen at the **guard** level (separate `CustomerGuard` and `MerchantGuard`), which is a much larger change.

## Recommendation

**Do not split now.** The current architecture is adequate. Instead:

1. **Document the resolver/guard contract** clearly (add docblocks)
2. **Add a `GuardFactory`** if dispatcher logic becomes complex
3. **Defer splitting** until at least one domain needs genuinely different resolution behavior (e.g., `admin` needs BoundaryGuard wired with different rules)
4. **Re-evaluate** when the `admin` domain guard requirements are defined

## If You Choose to Split

Estimated effort: **2-3 days**

```
app/Services/Ownership/Resolvers/
├── CustomerGuardResolver.php
├── MerchantGuardResolver.php
├── BoundaryGuardResolver.php
└── GuardResolverFactory.php   ← picks resolver by auth_domain
```

Tests: ~1 day to write isolated unit tests for each resolver.

## Summary

| Criterion | Verdict |
|-----------|---------|
| Technical benefit | Low (current design is simple and works) |
| Maintenance cost | Medium (more files, factory logic) |
| When to revisit | When guard behavior diverges per domain |
| Effort now | Not justified |
