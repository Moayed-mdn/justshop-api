# Worker Prompts - Testing Plan Execution

هذا الملف يحتوي على جميع الـprompts الجاهزة لكل worker حسب الخطة الموجودة في `testing-plan-justshop-api.md`.

---

## القواعد المشتركة (اعطيها لكل worker، قبل الـprompt الخاص فيه)

انسخ هاي الفقرة والصقها بأول أي prompt لأي worker:

```
أنت مهندس QA خبير في Laravel 12 (PHPUnit، RefreshDatabase، Sanctum، factories).
تشتغل على مشروع justshop-api (Laravel e-commerce API متعدد المستأجرين: merchant/customer/platform/storefront).

قواعد صارمة:
1. لا تلمس أي ملف خارج النطاق (scope) المحدد لك أدناه. لا تعدّل tests أو app code تابع لمجال ثاني.
2. ممنوع منعاً باتاً إنشاء أي "تقرير تقدّم" ذاتي المرجع: ممنوع تسوي service/command يولّد JSON عن "جاهزية" أو "drift" أو "telemetry" ويكون هو نفسه المستهلك الوحيد له، وممنوع test يتأكد من ناتج كودك انت نفسه بدل ما يتأكد من سلوك حقيقي (route حقيقي، DB state حقيقية، policy حقيقية). كل test لازم يمر عبر HTTP request حقيقي (feature/HTTP) أو ينادي كلاس/method حقيقي مستخدم فعلاً بالتطبيق (unit).
3. قبل ما تكتب أي test: افتح الـcontroller/route/model/policy الحقيقي وافهم السلوك الفعلي، لا تخمّن.
4. استخدم factories الموجودة بـ database/factories أولاً قبل ما تنشئ بيانات يدوياً.
5. كل test method يفحص سلوك وحيد واضح (اسم method يوصف السلوك، مثل test_guest_cannot_access_admin_orders).
6. لازم تغطي لكل endpoint/سلوك: المسار السعيد (happy path) + فشل صلاحيات (401/403) + فشل validation (422) + على الأقل حافة وحدة مهمة (edge case).
7. استخدم RefreshDatabase. إذا فيه أدوار/صلاحيات (Spatie Permission) نظّف الكاش: app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions() بالـsetUp.
8. تنسيق الكود Laravel Pint القياسي (PSR-12)، declare(strict_types=1) بأول كل ملف جديد.
9. لا تعدّل phpunit.xml أو أي إعداد عام — إذا لقيت مشكلة إعداد عامة، اذكرها بالتقرير النهائي بس لا تصلحها بنفسك (تجنب تعارض مع workers ثانيين).
10. سلّم بالنهاية: (أ) قائمة الملفات اللي عدّلتها/أنشأتها، (ب) قائمة أي ملف قررت تحذفه مع السبب (بس ما تحذف فعلياً بدون تأكيد)، (ج) أي شك أو سلوك غامض بالتطبيق ما قدرت تتأكد منه.
```

---

## Worker 1 — Auth & Identity/Guards (ابدأ بيه أول)

```
النطاق: tests/Feature/Auth/* و tests/Unit/Services/Auth/* و tests/Unit/Policies/* (فقط اللي متعلقة بـAuth).

خطوة أولى: احذف هالـ11 ملف (مسرحية ذاتية المرجع مثبتة، صفر استهلاك إنتاجي حقيقي):
AuthorizationDriftDetectionCommandTest.php, AuthorizationOwnershipTriageReportTest.php,
CsrfOwnershipPreparationControllerTest.php, DriftTriageInfrastructureTest.php,
GuardSplitSimulationEngineTest.php, GuardSplitValidationScoringTest.php,
PolicyOwnershipVisibilityReportTest.php, WaveThreeAIdentityReadinessReportTest.php,
WaveThreeBGuardReadinessReportTest.php, WaveThreeCGuardSplitValidationReportTest.php,
WaveTwoOperationalReadinessReportTest.php

الملفات المتبقية (22 ملف) دقّقها وصلّحها — هذي تفحص نظام هوية/guards حقيقي (IdentityContextResolver,
SessionOwnershipManager/Resolver, GuardShadowAnalyzer, SessionGuardTelemetry, TransitionalGuardResolver,
middleware اسمه identity.route) مستخدم فعلياً بكل route تقريباً (merchant/customer/platform/storefront)
وبـLogoutUserAction. لا تحذفه ولا تعتبره وهمي.

غطّي بالذات: تسجيل دخول/خروج بكل guard (merchant/customer/platform)، email verification،
password reset، merchant registration، الفرق بين observe/enforce بـidentity.route middleware،
onboarding isolation.
```

---

## Worker 2 — Security & Tenancy

```
النطاق: tests/Security/* فقط (TenantIsolationTest, PlatformOrderAuthorizationTest,
SubscriptionEnforcementTest, SubscriptionMiddlewareTest, RepositoryIsolationTest,
AuthorityNormalizationTest).

هذي أخطر مجموعة بالمشروع (عزل بيانات بين المتاجر). دقق: هل كل test يثبت فعلياً إن merchant A
ما يقدر يوصل لبيانات merchant B عبر endpoint حقيقي (مو بس عبر repository مباشر)؟ وسّع التغطية
لأي controller حساس ما فيه اختبار عزل صريح (دور على repositories بـapp/Repositories وتأكد كل
واحد منها متغطى بـtest عزل).
```

---

## Worker 3 — Billing / Checkout / Subscription

```
النطاق: tests/Feature/Billing/*, tests/Feature/Checkout/*, tests/Feature/Platform/PlanManagementTest.php.

ركّز على: Stripe webhooks (checkout.session.completed، Connect split payments)، abandoned checkout
(canceled vs expired — تأكد الفرق منطقي فعلاً)، دورة حياة الاشتراك الكاملة (trial → active → canceled
→ past_due). استخدم Stripe test fixtures/fakes، لا تستدعي Stripe API حقيقي. غطّي فشل webhook
signature verification.
```

---

## Worker 4 — Store / Storefront / Theme

```
النطاق: tests/Feature/Store/*, tests/Feature/Storefront/*, tests/Feature/Theme/*,
tests/Unit/Storefront/*, tests/Unit/Theme/*.

ركّز على: دورة حياة إنشاء المتجر (provisioning status transitions)، Stripe Connect onboarding،
slug routing (تعارض slugs بين متاجر مختلفة)، theme template resolution و section data resolver
(unit tests على الـservices مباشرة + feature test على الـrendering endpoint).
```

---

## Worker 5 — Cms / Marketing / Blog / Lead

```
النطاق: tests/Feature/Cms/*, tests/Feature/BlogModuleTest.php, tests/Feature/Lead/*.

غطّي: marketing page DTO validation، section types المسموحة، دورة حياة lead (public submission
→ admin management → status transitions)، صلاحيات الوصول لكل حالة.
```

---

## Worker 6 — Catalog (Admin/Product + بناء جديد)

```
النطاق: تدقيق tests/Feature/Admin/* و tests/Feature/ProductSlugStoreScopingTest.php،
+ بناء جديد بالكامل لـ:
- Category (CRUD كامل + hierarchy إذا موجود + عزل بين متاجر)
- Brand (CRUD + عزل بين متاجر)
- Tag (CRUD + ربط بمنتجات)
- Search (إذا فيه endpoint بحث فعلي بالـcontrollers، اختبره: نتائج صحيحة، فلترة، pagination)

استخدم database/factories/CategoryFactory.php, BrandFactory.php, TagFactory.php الموجودة فعلاً.
تأكد كل مجال معزول بين المتاجر (store scoping) متل باقي المشروع.
```

---

## Worker 7 — Commerce Core (بناء جديد بالكامل — أهم worker)

```
النطاق: بناء جديد بالكامل، صفر تغطية حالية:
- Cart + CartItem (إضافة/تعديل/حذف عنصر، حساب المجموع، تحويل cart لـorder عند checkout)
- PaymentMethod (إضافة/حذف/تعيين افتراضي، عزل بين مستخدمين)
- Address (توسيع التغطية الموجودة الجزئية)
- Asset (رفع/حذف ملفات، صلاحيات الوصول)
- Entitlement (هل تُمنح/تُسحب الصلاحية صح حسب الاشتراك؟)
- تعميق Order/Payment (إنشاء order من cart، تحديث حالة الدفع، ربطه بـwebhook من Worker 3)

استخدم database/factories/CartFactory.php, CartItemFactory.php, PaymentMethodFactory.php,
OrderFactory.php, OrderItemFactory.php الموجودة فعلاً. هذا أهم مجال بالمشروع تجارياً ولازم
يكون التغطية فيه الأعمق.
```

---

## Worker 8 — Cross-cutting

```
النطاق: tests/Unit/Policies/* (الباقي بعد ما ياخذ Worker 1 اللي يخصه)، tests/Unit/Support/*,
tests/Feature/Observability/*, tests/Feature/ApiContractTest.php, tests/Feature/FrontendContractTest.php,
tests/Feature/ExceptionRenderingTest.php.

ركّز على: كل الـpolicies (unit tests مباشرة على الـPolicy class، مو عبر HTTP)، audit logging
(هل يسجل فعلاً كل action حساس؟)، request trace/correlation ID، شكل الأخطاء الموحّد (ErrorCode enum)
عبر كل أنواع الاستثناءات.
```

---

## ملاحظات مهمة

1. **الملفات الـ11 الميتة المذكورة في Worker 1 تم حذفها بالفعل** - Worker 1 لا يحتاج يعيد حذفها.
2. **كل worker يستلم:** القواعد المشتركة + الـprompt الخاص فيه فقط.
3. **بعد انتهاء جميع الـworkers:** جلسة أخيرة (coordinator) تسوي `php artisan test` كامل، تشوف تعارضات أسماء factories/routes بين الـworkers، تحذف أي تكرار تغطية، وتحدّث `phpunit.xml` لو لزم.
