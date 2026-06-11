# Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         YOUR MONOREPO STRUCTURE                         │
└─────────────────────────────────────────────────────────────────────────┘

                              tenant/
                                │
                ┌───────────────┼───────────────┐
                │               │               │
                ▼               ▼               ▼
        ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
        │   Nuxt.js    │ │   Next.js    │ │   Laravel    │
        │  Storefront  │ │   Merchant   │ │   Backend    │
        │              │ │   Dashboard  │ │     API      │
        └──────────────┘ └──────────────┘ └──────────────┘
             Port            Port             Port
             3002            4000             8000


┌─────────────────────────────────────────────────────────────────────────┐
│                    CUSTOMER (STOREFRONT) - PORT 3002                    │
└─────────────────────────────────────────────────────────────────────────┘

    Browser: http://localhost:3002
    
    ┌─────────────┐
    │   Customer  │ 👤
    └──────┬──────┘
           │
           ▼
    ┌─────────────────────────────────────────┐
    │     Nuxt.js Application (Port 3002)     │
    ├─────────────────────────────────────────┤
    │ Routes:                                 │
    │  ✅ /en                  (Home)         │
    │  ✅ /en/shop             (Products)     │
    │  ✅ /en/cart             (Cart)         │
    │  ✅ /en/login            (Login)        │
    │  ✅ /en/register         (Register)     │
    │  ✅ /en/orders           (Orders)       │
    │  ✅ /en/profile          (Profile)      │
    │                                         │
    │  ❌ /en/merchant/*       (NOT HERE!)    │
    │  ❌ /en/stores/*         (NOT HERE!)    │
    └─────────────────┬───────────────────────┘
                      │
                      │ API Calls
                      ▼
            ┌──────────────────┐
            │   Laravel API    │
            │   Port 8000      │
            └──────────────────┘


┌─────────────────────────────────────────────────────────────────────────┐
│                  MERCHANT (DASHBOARD) - PORT 4000                       │
└─────────────────────────────────────────────────────────────────────────┘

    Browser: http://localhost:4000
    
    ┌─────────────┐
    │   Merchant  │ 👔
    └──────┬──────┘
           │
           ▼
    ┌─────────────────────────────────────────┐
    │    Next.js Application (Port 4000)      │
    ├─────────────────────────────────────────┤
    │ Routes:                                 │
    │  ✅ /en/login                           │
    │  ✅ /en/signup                          │
    │  ✅ /en/setup                           │
    │  ✅ /en/merchant/dashboard              │
    │  ✅ /en/merchant/products               │
    │  ✅ /en/merchant/brands      ⭐         │
    │  ✅ /en/merchant/tags        ⭐         │
    │  ✅ /en/merchant/categories             │
    │  ✅ /en/merchant/orders                 │
    │  ✅ /en/merchant/stores                 │
    │  ✅ /en/stores/3/brands/new  ⭐         │
    │  ✅ /en/stores/3/tags/new    ⭐         │
    └─────────────────┬───────────────────────┘
                      │
                      │ API Calls
                      ▼
            ┌──────────────────┐
            │   Laravel API    │
            │   Port 8000      │
            └──────────────────┘


┌─────────────────────────────────────────────────────────────────────────┐
│                         YOUR PROBLEMS MAPPED                            │
└─────────────────────────────────────────────────────────────────────────┘

    ❌ PROBLEM 1: Accessing merchant routes on wrong port
    
    You did:
    ┌────────────────────────────────────────┐
    │ http://localhost:3002/en/merchant/     │ ← Port 3002 (Nuxt)
    │                brands/new              │
    └────────────────────────────────────────┘
                       │
                       ▼
                  ⚠️  404 Not Found
                  (Route doesn't exist in Nuxt!)
    
    You should do:
    ┌────────────────────────────────────────┐
    │ http://localhost:4000/en/merchant/     │ ← Port 4000 (Next.js)
    │                brands/new              │
    └────────────────────────────────────────┘
                       │
                       ▼
                  ✅  200 OK
                  (Route exists in Next.js!)


    ❌ PROBLEM 2: Double locale prefix /en/en
    
    What happened:
    ┌──────────────────────────────────────────────────────┐
    │  Next.js (Port 4000) session expires                 │
    │  Generates: /en/login?redirect=/en/merchant/dashboard│
    └────────────────────┬─────────────────────────────────┘
                         │
                         │ Somehow URL opens on
                         │ wrong port (3002)
                         ▼
    ┌──────────────────────────────────────────────────────┐
    │  Nuxt.js (Port 3002) sees incoming request           │
    │  i18n middleware adds locale prefix again            │
    │  Result: /en/en/login                                │
    └────────────────────┬─────────────────────────────────┘
                         │
                         │ Middleware fix applied
                         ▼
    ┌──────────────────────────────────────────────────────┐
    │  fix-double-locale.global.ts detects /en/en          │
    │  Redirects to: /en/login                             │
    │  ✅ Fixed!                                           │
    └──────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────────────┐
│                          THE SOLUTION                                   │
└─────────────────────────────────────────────────────────────────────────┘

    ┌─────────────────────────────────────────────────────┐
    │  RULE 1: Always use correct port for each feature  │
    ├─────────────────────────────────────────────────────┤
    │  Shopping, Cart, Customer Login   → Port 3002      │
    │  Brands, Tags, Merchant Dashboard → Port 4000      │
    └─────────────────────────────────────────────────────┘

    ┌─────────────────────────────────────────────────────┐
    │  RULE 2: Never mix URLs between applications       │
    ├─────────────────────────────────────────────────────┤
    │  ❌ Don't copy port 4000 URL to port 3002 browser  │
    │  ✅ Use bookmarks for each application             │
    └─────────────────────────────────────────────────────┘

    ┌─────────────────────────────────────────────────────┐
    │  RULE 3: Middleware will catch /en/en errors       │
    ├─────────────────────────────────────────────────────┤
    │  Automatic redirect to correct URL                 │
    │  Check console for warnings                        │
    └─────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────────────┐
│                         QUICK DECISION TREE                             │
└─────────────────────────────────────────────────────────────────────────┘

    "I want to..."
         │
         ├─ Browse products as customer ───────→ Port 3002
         │
         ├─ Add items to cart ─────────────────→ Port 3002
         │
         ├─ Login as customer ─────────────────→ Port 3002
         │
         ├─ Manage my store products ──────────→ Port 4000
         │
         ├─ Create/edit brands ────────────────→ Port 4000 ⭐
         │
         ├─ Create/edit tags ──────────────────→ Port 4000 ⭐
         │
         ├─ View merchant dashboard ───────────→ Port 4000
         │
         └─ Login as merchant ─────────────────→ Port 4000


┌─────────────────────────────────────────────────────────────────────────┐
│                         BOOKMARK TEMPLATE                               │
└─────────────────────────────────────────────────────────────────────────┘

    📁 JustShop Development
       │
       ├─📁 Storefront (Customer - Port 3002)
       │  ├─🔖 Home: http://localhost:3002/en
       │  ├─🔖 Shop: http://localhost:3002/en/shop
       │  ├─🔖 Cart: http://localhost:3002/en/cart
       │  └─🔖 Login: http://localhost:3002/en/login
       │
       ├─📁 Merchant Dashboard (Admin - Port 4000)
       │  ├─🔖 Login: http://localhost:4000/en/login
       │  ├─🔖 Dashboard: http://localhost:4000/en/merchant/dashboard
       │  ├─🔖 Brands: http://localhost:4000/en/merchant/brands ⭐
       │  ├─🔖 Tags: http://localhost:4000/en/merchant/tags ⭐
       │  └─🔖 Store 3 Brands: http://localhost:4000/en/stores/3/brands/new
       │
       └─📁 API (Backend - Port 8000)
          └─🔖 Health: http://localhost:8000/api/health


┌─────────────────────────────────────────────────────────────────────────┐
│                       REMEMBER THIS ONE THING                           │
└─────────────────────────────────────────────────────────────────────────┘

                    ╔═══════════════════════════╗
                    ║  Port 3002 = 🛍️ Shopping  ║
                    ║  Port 4000 = 👔 Managing  ║
                    ╚═══════════════════════════╝

                        That's all you need!
