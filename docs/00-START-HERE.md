# 🚀 Laravel Backend Documentation - START HERE

**Project**: Multi-Tenant E-Commerce Platform (laratenant-backend)  
**Framework**: Laravel 11  
**Architecture**: Domain-Driven, API-First  
**Date**: June 7, 2026

---

## 📖 Quick Navigation

### For New Developers
1. **Read**: [ARCHITECTURE.md](./ARCHITECTURE.md) - **MUST READ FIRST** ⭐
2. **Read**: [AI_RULES_ENFORCEMENT_SYSTEM.md](./AI_RULES_ENFORCEMENT_SYSTEM.md) - How to work with AI
3. **Read**: [EXECUTION_GOVERNANCE.md](./EXECUTION_GOVERNANCE.md) - Execution rules
4. **Browse**: [Documentation by Category](#documentation-by-category)

### For AI Assistants
**MANDATORY**: Read [AI_RULES_ENFORCEMENT_SYSTEM.md](./AI_RULES_ENFORCEMENT_SYSTEM.md) before doing ANY work.

### For Quick Reference
- **Architecture Rules**: [ARCHITECTURE.md](./ARCHITECTURE.md)
- **Auth Routing**: [AUTH_ROUTING.md](./AUTH_ROUTING.md)
- **Admin API**: [admin-api.md](./admin-api.md)
- **CMS Architecture**: [CMS_MARKETING_ARCHITECTURE.md](./CMS_MARKETING_ARCHITECTURE.md)
- **Exception System**: [exception-system.md](./exception-system.md)

---

## 🏗️ Project Architecture Overview

### Core Principles
1. **Domain-First Structure** - Group by business domain, not technical type
2. **Strict Separation of Concerns** - Controllers, Actions, DTOs, Repositories
3. **Multi-Tenant Isolation** - ALL queries scoped by store_id
4. **Policy-Based Authorization** - Authorization ONLY in Policies
5. **API-First** - Clean RESTful API with standardized responses

### Golden Path Flow
```
Request → FormRequest → DTO → Action → Repository → Model
                                ↓
                           Resource → ApiResponserTrait → Response
```

### Critical Rules
- ❌ **NO database enums** (use PHP enums)
- ✅ **Store scoping MANDATORY** on all commerce queries
- ✅ **DTOs required** with storeId as first parameter
- ✅ **Thin controllers** (10-15 lines)
- ✅ **Authorization ONLY in Policies**
- ✅ **Localization for all messages**

---

## 📚 Documentation by Category

### 🏛️ Architecture & Governance
**Essential Reading - Start Here**

| Document | Description | Priority |
|----------|-------------|----------|
| [ARCHITECTURE.md](./ARCHITECTURE.md) | **Supreme architecture law** | ⭐⭐⭐ |
| [EXECUTION_GOVERNANCE.md](./EXECUTION_GOVERNANCE.md) | Execution and migration rules | ⭐⭐⭐ |
| [AI_RULES_ENFORCEMENT_SYSTEM.md](./AI_RULES_ENFORCEMENT_SYSTEM.md) | How to make AI follow rules | ⭐⭐⭐ |
| [exception-system.md](./exception-system.md) | Error handling architecture | ⭐⭐ |
| [OBSERVABILITY.md](./OBSERVABILITY.md) | Monitoring and logging | ⭐⭐ |

### 🔐 Authentication & Authorization
| Document | Description |
|----------|-------------|
| [AUTH_ROUTING.md](./AUTH_ROUTING.md) | Auth routing structure |
| [auth/](./auth/) | Auth implementation docs |
| [security/](./security/) | Security guidelines |

### 🛍️ Domain Documentation
| Domain | Location | Description |
|--------|----------|-------------|
| **CMS & Marketing** | [CMS_MARKETING_ARCHITECTURE.md](./CMS_MARKETING_ARCHITECTURE.md) | CMS architecture |
| **Admin API** | [admin-api.md](./admin-api.md) | Admin endpoints |
| **Products** | [features/](./features/) | Product features |
| **Orders** | [features/](./features/) | Order features |
| **Stores** | [features/](./features/) | Store management |

### 🔄 Wave-Based Migrations
| Wave | Status | Documents |
|------|--------|-----------|
| Wave 1 | Complete | [WAVE1_REMEDIATION_REPORT.md](./WAVE1_REMEDIATION_REPORT.md) |
| Wave 2 | Complete | [wave2/](./wave2/) |
| Wave 5 | Complete | [wave5-runtime-authority-activation.md](./wave5-runtime-authority-activation.md) |
| Wave 6 | Complete | [wave6/](./wave6/) |
| Wave 7 | Complete | [wave7/](./wave7/) |
| Wave 8 | Complete | [wave8/](./wave8/) |
| Wave 9 | Complete | [wave9/](./wave9/) |
| Wave 10 | Complete | [wave10/](./wave10/) |
| Wave 11 | Complete | [wave11/](./wave11/) |
| Wave 12 | Complete | [wave12/](./wave12/) |
| **Closure** | ✅ | [wave-closure-audit.md](./wave-closure-audit.md) |

### 📋 Implementation Guides
| Document | Description |
|----------|-------------|
| [GENERIC_IMAGE_UPLOAD.md](./GENERIC_IMAGE_UPLOAD.md) | Image upload system |
| [MARKETING_PAGES_IMPLEMENTATION_SUMMARY.md](./MARKETING_PAGES_IMPLEMENTATION_SUMMARY.md) | Marketing pages |
| [implementation/](./implementation/) | Implementation guides |
| [migrations/](./migrations/) | Migration guides |

### 🧪 Testing & Quality
| Document | Description |
|----------|-------------|
| [testing/](./testing/) | Test guides and results |
| [production-hardening-checklist.md](./production-hardening-checklist.md) | Production readiness |

### 🐛 Fixes & Solutions
| Location | Description |
|----------|-------------|
| [fixes/](./fixes/) | Bug fixes and resolutions |
| [reports/](./reports/) | Status reports |

### 🎯 Quick References
| Document | Description |
|----------|-------------|
| [quick-reference/](./quick-reference/) | Quick start guides |
| [reference/](./reference/) | API references |

### 📊 Planning & Decisions
| Document | Description |
|----------|-------------|
| [adr/](./adr/) | Architecture Decision Records |
| [plans/](./plans/) | Planning documents |
| [audits/](./audits/) | Audit reports |

### 🚨 Operations
| Document | Description |
|----------|-------------|
| [runbooks/](./runbooks/) | Operational runbooks |
| [alerts/](./alerts/) | Alert configurations |
| [dashboards/](./dashboards/) | Monitoring dashboards |

---

## 🎯 Common Tasks

### I want to...

#### **Add a new feature**
1. Read [ARCHITECTURE.md](./ARCHITECTURE.md) rules
2. Use [AI_RULES_ENFORCEMENT_SYSTEM.md](./AI_RULES_ENFORCEMENT_SYSTEM.md) prompt templates
3. Follow the Golden Path
4. Ensure store scoping

#### **Fix a bug**
1. Check [fixes/](./fixes/) for similar issues
2. Follow [ARCHITECTURE.md](./ARCHITECTURE.md) rules
3. Maintain architecture compliance
4. Add to fixes/ when complete

#### **Work with AI**
1. **MUST READ**: [AI_RULES_ENFORCEMENT_SYSTEM.md](./AI_RULES_ENFORCEMENT_SYSTEM.md)
2. Use provided prompt templates
3. Reference ARCHITECTURE.md in every prompt
4. Verify AI output against checklist

#### **Understand auth flow**
1. Read [AUTH_ROUTING.md](./AUTH_ROUTING.md)
2. Check [auth/](./auth/) folder
3. Review [security/](./security/) guidelines

#### **Deploy to production**
1. Read [production-hardening-checklist.md](./production-hardening-checklist.md)
2. Follow [EXECUTION_GOVERNANCE.md](./EXECUTION_GOVERNANCE.md)
3. Check [runbooks/](./runbooks/) for operations

#### **Understand a wave migration**
1. Check [wave-closure-audit.md](./wave-closure-audit.md) for overview
2. Read specific wave folder (wave6/, wave7/, etc.)
3. Review [EXECUTION_GOVERNANCE.md](./EXECUTION_GOVERNANCE.md) for rules

---

## ⚠️ Critical Rules Summary

### Database Rules
```php
// ❌ FORBIDDEN
$table->enum('status', ['pending', 'paid']);

// ✅ REQUIRED
$table->string('status');
enum OrderStatusEnum: string { case PENDING = 'pending'; }
```

### Store Scoping Rules
```php
// ❌ FORBIDDEN
Product::find($id);
Order::where('status', 'pending')->get();

// ✅ REQUIRED
Product::where('store_id', $storeId)->findOrFail($id);
Order::where('store_id', $storeId)->where('status', 'pending')->get();
```

### Authorization Rules
```php
// ❌ FORBIDDEN (in Action)
if (!Auth::user()->can('delete')) {
    throw new UnauthorizedException();
}

// ✅ REQUIRED (in Controller)
$this->authorize('delete', $product);

// ✅ REQUIRED (in Policy)
public function delete(User $user, Product $product): bool {
    return $user->can('products.delete');
}
```

### Controller Rules
```php
// ✅ REQUIRED - Thin controller (10-15 lines)
public function store(CreateProductRequest $request, int $store): JsonResponse {
    $product = $this->createProductAction->execute(
        CreateProductDTO::fromRequest($request, $store)
    );
    return $this->success(new ProductResource($product));
}
```

### DTO Rules
```php
// ✅ REQUIRED - storeId FIRST, strongly typed
public function __construct(
    public int $storeId,        // ← MUST be first
    public string $name,
    public int $productId,
) {}
```

---

## 📞 Getting Help

### Questions About...

**Architecture**: Read [ARCHITECTURE.md](./ARCHITECTURE.md) first  
**AI Usage**: Read [AI_RULES_ENFORCEMENT_SYSTEM.md](./AI_RULES_ENFORCEMENT_SYSTEM.md)  
**Execution**: Read [EXECUTION_GOVERNANCE.md](./EXECUTION_GOVERNANCE.md)  
**Specific Feature**: Check [features/](./features/) folder  
**Bug Fixes**: Check [fixes/](./fixes/) folder  
**Testing**: Check [testing/](./testing/) folder  

---

## 📈 Project Status

**Architecture**: ✅ Mature and stable  
**Wave Migrations**: ✅ Complete (Waves 1-12)  
**Documentation**: ✅ Comprehensive  
**Production**: ✅ Ready  

---

## 🎓 Learning Path

### Day 1: Understand Architecture
- [ ] Read [ARCHITECTURE.md](./ARCHITECTURE.md) completely
- [ ] Understand Golden Path flow
- [ ] Review folder structure rules
- [ ] Study critical rules (enums, store scoping, authorization)

### Day 2: Learn AI Collaboration
- [ ] Read [AI_RULES_ENFORCEMENT_SYSTEM.md](./AI_RULES_ENFORCEMENT_SYSTEM.md)
- [ ] Practice with prompt templates
- [ ] Understand rule enforcement
- [ ] Review common AI mistakes

### Day 3: Explore Domains
- [ ] Review [CMS_MARKETING_ARCHITECTURE.md](./CMS_MARKETING_ARCHITECTURE.md)
- [ ] Check [admin-api.md](./admin-api.md)
- [ ] Browse [features/](./features/) folder
- [ ] Understand domain structure

### Day 4: Operations & Deployment
- [ ] Read [production-hardening-checklist.md](./production-hardening-checklist.md)
- [ ] Review [EXECUTION_GOVERNANCE.md](./EXECUTION_GOVERNANCE.md)
- [ ] Check [runbooks/](./runbooks/)
- [ ] Understand deployment process

### Week 2+: Deep Dive
- [ ] Study wave migrations history
- [ ] Review ADRs in [adr/](./adr/)
- [ ] Explore testing practices
- [ ] Contribute to documentation

---

## ✅ Success Criteria

You understand this project when you can:

1. ✅ Explain why database enums are forbidden
2. ✅ Write a query with proper store scoping
3. ✅ Create a DTO with correct structure
4. ✅ Build a thin controller following Golden Path
5. ✅ Place authorization in Policy (not Action)
6. ✅ Make AI follow architecture rules strictly
7. ✅ Navigate documentation efficiently
8. ✅ Implement features architecture-compliant

---

## 🚀 Ready to Start?

1. **Read**: [ARCHITECTURE.md](./ARCHITECTURE.md) ⭐
2. **Read**: [AI_RULES_ENFORCEMENT_SYSTEM.md](./AI_RULES_ENFORCEMENT_SYSTEM.md) ⭐
3. **Browse**: This START-HERE file
4. **Explore**: Relevant domain folders
5. **Build**: Following the rules!

---

**Welcome to the team!** 🎉

This is a mature, well-architected project with strict rules for good reason. Follow the architecture, use AI wisely, and you'll build great features efficiently.

---

**Last Updated**: June 7, 2026  
**Status**: Active  
**Authority**: ARCHITECTURE.md
