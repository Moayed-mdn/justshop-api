# خطة اختبارات justshop-api — تحليل + توزيع العمل على عدة AI

فحصت الملف كاملاً (فك الضغط + قراءة الكود فعلياً، مو بس أسماء الملفات). هاي نتائج التحليل والقرار والخطة.

---

## 1) شنو لقيت فعلياً

- المشروع: Laravel 12، **87 controller**، **69 model**، **78 ملف test** حالي (Feature/Unit/Security).
- **لا يوجد `phpunit.xml` إطلاقاً** في المشروع، ولا اتصال قاعدة بيانات مخصص للاختبار (sqlite/`:memory:`). يعني حرفياً ما بتقدر تشغل `php artisan test` بشكل نظيف قبل ما نصلح هاي. رفقت لك `phpunit.xml` جاهز (استخدمه فوق).
- **اكتشاف رئيسي:** جزء من `tests/Feature/Auth` مو اختبارات حقيقية — هو "مسرحية تقدّم" (progress theater) كتبها AI أثناء شغله على ترحيل نظام الـguards (merchant/customer/platform). AI عمل خدمات وأوامر (`GenerateWaveThreeAIdentityReadinessReportCommand`, `DetectAuthorizationDriftCommand`, `GuardSplitSimulationService`...) تولّد تقارير JSON عن "جاهزية" الترحيل مقسّمة لـ"موجات" (Wave1 → Wave7)، وبعدين كتب tests تتأكد إن الـJSON اللي هي نفسها ولّدته صحيح. تحققت بـgrep شامل على كل المشروع: **هالطبقة كاملة (10 services + 13 command + 1 controller ميت) ما يستهلكها أي كود إنتاجي حقيقي — صفر مرجع خارج نفسها.**
- **بالمقابل**، فيه نظام حقيقي وحي لإدارة الهوية/الـguards (`IdentityContextResolver`, `SessionOwnershipManager/Resolver`, `GuardShadowAnalyzer`, `SessionGuardTelemetry`, `TransitionalGuardResolver`, middleware اسمه `identity.route`) **مستخدم فعلياً في تسجيل الدخول/الخروج وعشرات الـroutes** (merchant/customer/platform/storefront). هذا مو مسرحية — هذا كود إنتاجي حقيقي ويحتاج اختبارات دقيقة، مو حذف.
- **فجوة تغطية كبيرة وأهم من موضوع الـAuth:** مجالات تجارية أساسية عندها factories جاهزة (يعني الفيتشر موجود ومستخدم) بس **صفر ملف test**: `Cart`, `CartItem`, `Category`, `Brand`, `Tag`, `Asset`, `Entitlement`, `PaymentMethod`. حتى `Order` و`Payment` عندهم تغطية شبه معدومة.
- خارج `Auth`، عيّنة فحصتها من `Billing/Checkout/Cms/Lead/Observability/Security/Blog` + `ApiContractTest`/`FrontendContractTest` تبدو **اختبارات حقيقية** تفحص routes/models/قواعد عمل فعلية — مو مسرحية.

---

## 2) القرار: **لا** لحذف الكل والبدء من الصفر

اقتراحك (احذف الكل وابدأ من جديد) مفهوم لأن الأسماء الغريبة توحي بفوضى شاملة، بس بناءً على التحليل الفعلي: **حذف كل الـ78 ملف رمي لعمل حقيقي وصحيح (أغلبية الملفات)، ومخاطرة إضافية إنك تنسى تغطي حافة (edge case) كانت موجودة صدفة صح.** القرار الأدق:

### أ) احذف نهائياً (test + الكود المسانِد) — 11 ملف test + 24 ملف app، صفر قيمة:

**Tests:**
```
tests/Feature/Auth/AuthorizationDriftDetectionCommandTest.php
tests/Feature/Auth/AuthorizationOwnershipTriageReportTest.php
tests/Feature/Auth/CsrfOwnershipPreparationControllerTest.php
tests/Feature/Auth/DriftTriageInfrastructureTest.php
tests/Feature/Auth/GuardSplitSimulationEngineTest.php
tests/Feature/Auth/GuardSplitValidationScoringTest.php
tests/Feature/Auth/PolicyOwnershipVisibilityReportTest.php
tests/Feature/Auth/WaveThreeAIdentityReadinessReportTest.php
tests/Feature/Auth/WaveThreeBGuardReadinessReportTest.php
tests/Feature/Auth/WaveThreeCGuardSplitValidationReportTest.php
tests/Feature/Auth/WaveTwoOperationalReadinessReportTest.php
```

**App code (services/commands/controller ميتة — صفر مرجع خارجي):**
```
app/Services/Auth/GuardSplitSimulationService.php
app/Services/Auth/GuardSplitReadinessScoringService.php
app/Services/Auth/FrontendGuardSplitReadinessService.php
app/Services/Auth/TransitionalDebtMeasurer.php
app/Services/Auth/TransitionalDependencyAnalyzer.php
app/Services/Auth/Drift/AuthorizationOwnershipTriageService.php
app/Services/Auth/Readiness/WaveThreeAIdentityReadinessReportService.php
app/Services/Auth/Readiness/WaveThreeBGuardReadinessReportService.php
app/Services/Auth/Readiness/WaveThreeCGuardSplitValidationReportService.php
app/Services/Auth/Readiness/WaveTwoOperationalReadinessReportService.php
app/Console/Commands/DetectAuthorizationDriftCommand.php
app/Console/Commands/GenerateWaveThreeAIdentityReadinessReportCommand.php
app/Console/Commands/GenerateWaveThreeBGuardReadinessReportCommand.php
app/Console/Commands/GenerateWaveThreeCGuardSplitValidationReportCommand.php
app/Console/Commands/GenerateWaveTwoOperationalReadinessReportCommand.php
app/Console/Commands/GenerateAuthorizationOwnershipTriageCommand.php
app/Console/Commands/Architecture/Wave1ReadinessCommand.php
app/Console/Commands/Architecture/Wave4ReadinessCommand.php
app/Console/Commands/Architecture/Wave5ReadinessCommand.php
app/Console/Commands/Architecture/Wave6ReadinessCommand.php
app/Console/Commands/Architecture/Wave7ReadinessCommand.php
app/Console/Commands/Architecture/GenerateWave6ArtifactsCommand.php
app/Console/Commands/Architecture/ProviderExtractionReadinessReportCommand.php
app/Http/Controllers/Api/Shared/Auth/Preparation/CsrfOwnershipPreparationController.php
```
> ⚠️ هاي كود إنتاجي (مو tests بس)، فحتى لو تحليلي واثق 100%، خلي عندك نظرة سريعة (`git blame`) قبل الحذف الفعلي — بس ما تعطيه لأي AI worker يحذفه تلقائياً، خليه قرارك الأخير.

### ب) دقّق ورقّع (Patch)، لا تحذف — الباقي (~67 ملف)
هذي فيها كود حقيقي يفحص سلوك فعلي، وبعضها كتبه AI صح فعلاً (مثل `BlogModuleTest`, `AdminLeadManagementTest`, `TenantIsolationTest`, `ThemePolicyTest`). الشغل هنا هو تدقيق + تصليح، مو إعادة كتابة من الصفر.

### ج) بناء جديد كامل (Net-new) — الأولوية الحقيقية
`Cart`, `CartItem`, `Category`, `Brand`, `Tag`, `Asset`, `Entitlement`, `PaymentMethod`, وتعميق `Order`/`Payment`. هذا الجزء الأهم فعلياً لأنه المسارات التجارية الأساسية بدون أي تغطية.

---

## 3) خطوة تمهيدية (لازم تصير أول شي، قبل أي worker)

1. حط `phpunit.xml` المرفق في جذر المشروع.
2. تأكد `pdo_sqlite` مفعّل بالـPHP المحلي.
3. شغّل `php artisan test` مرة وحدة بس لتتأكد الإعداد شغال (توقع فشل بعض الاختبارات — طبيعي، هذا اللي رح نصلحه).

---

## 4) توزيع العمل على عدة AI — 7 workers متوازيين + مرحلة دمج نهائية

**ليش هيك تقسيم:** كل worker ياخذ **مجال محدود (bounded domain)** بدون تداخل ملفات مع غيره — هذا يمنع تعارض الدمج (merge conflicts) ويخلي context كل واحد مركّز وما يتوه بمشروع كبير. الترتيب مو مهم بينهم (يقدروا يشتغلوا بالتوازي)، عدا worker 1 (Auth) مستحسن يبلش أول لأنه الأخطر.

| # | Worker | النطاق | نوع الشغل |
|---|--------|--------|-----------|
| 1 | Auth & Identity/Guards | `tests/Feature/Auth/*` (الـ22 ملف الحقيقية) + `tests/Unit/Services/Auth/*` | حذف الـ11 الميتة (بعد موافقتك) + تدقيق/ترقيع الباقي |
| 2 | Security & Tenancy | `tests/Security/*` | تدقيق/ترقيع + توسيع |
| 3 | Billing/Checkout/Subscription | `tests/Feature/Billing/*`, `tests/Feature/Checkout/*`, `tests/Feature/Platform/PlanManagementTest.php` | تدقيق/ترقيع + توسيع |
| 4 | Store/Storefront/Theme | `tests/Feature/Store/*`, `tests/Feature/Storefront/*`, `tests/Feature/Theme/*`, `tests/Unit/Storefront/*`, `tests/Unit/Theme/*` | تدقيق/ترقيع |
| 5 | Cms/Marketing/Blog/Lead | `tests/Feature/Cms/*`, `tests/Feature/BlogModuleTest.php`, `tests/Feature/Lead/*` | تدقيق/ترقيع + توسيع |
| 6 | Catalog (Admin/Product جديد) | `tests/Feature/Admin/*`, `tests/Feature/ProductSlugStoreScopingTest.php` + **جديد كلياً:** Category, Brand, Tag, Search | تدقيق للموجود + بناء جديد للفجوة |
| 7 | Commerce Core (جديد كلياً) | Cart, CartItem, PaymentMethod, Address (توسيع), Asset, Entitlement, Order/Payment (تعميق) | بناء جديد بالكامل |
| 8 | Cross-cutting | `tests/Unit/Policies/*`, `tests/Unit/Support/*`, `tests/Feature/Observability/*`, `ApiContractTest`, `FrontendContractTest`, `ExceptionRenderingTest` | تدقيق/ترقيع |

(8 workers فعلياً — رقمتهم من 1 بدل 0 عشان الوضوح، بس هذا العدد المناسب لحجم المشروع: مو أكثر من اللازم بحيث يصير التنسيق صعب، ومو أقل من اللازم بحيث يصير context كل واحد ضخم وبيبلش يهلوس.)

**بعد ما يخلص الكل:** جلسة أخيرة (تسويها انت، أو أعطيها لأي AI كـ"coordinator") تسوي:
`php artisan test` كامل، تشوف تعارضات أسماء factories/routes بين الـworkers، تحذف أي تكرار تغطية، وتحدّث `phpunit.xml` لو لزم.

---

## 5) القواعد المشتركة (اعطيها لكل worker، قبل الـprompt الخاص فيه)

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

## 6) الـprompts الخاصة بكل worker (ألصق فوق كل وحدة القواعد المشتركة #5)

### Worker 1 — Auth & Identity/Guards (ابدأ بيه أول)
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

### Worker 2 — Security & Tenancy
```
النطاق: tests/Security/* فقط (TenantIsolationTest, PlatformOrderAuthorizationTest,
SubscriptionEnforcementTest, SubscriptionMiddlewareTest, RepositoryIsolationTest,
AuthorityNormalizationTest).

هذي أخطر مجموعة بالمشروع (عزل بيانات بين المتاجر). دقق: هل كل test يثبت فعلياً إن merchant A
ما يقدر يوصل لبيانات merchant B عبر endpoint حقيقي (مو بس عبر repository مباشر)؟ وسّع التغطية
لأي controller حساس ما فيه اختبار عزل صريح (دور على repositories بـapp/Repositories وتأكد كل
واحد منها متغطى بـtest عزل).
```

### Worker 3 — Billing / Checkout / Subscription
```
النطاق: tests/Feature/Billing/*, tests/Feature/Checkout/*, tests/Feature/Platform/PlanManagementTest.php.

ركّز على: Stripe webhooks (checkout.session.completed، Connect split payments)، abandoned checkout
(canceled vs expired — تأكد الفرق منطقي فعلاً)، دورة حياة الاشتراك الكاملة (trial → active → canceled
→ past_due). استخدم Stripe test fixtures/fakes، لا تستدعي Stripe API حقيقي. غطّي فشل webhook
signature verification.
```

### Worker 4 — Store / Storefront / Theme
```
النطاق: tests/Feature/Store/*, tests/Feature/Storefront/*, tests/Feature/Theme/*,
tests/Unit/Storefront/*, tests/Unit/Theme/*.

ركّز على: دورة حياة إنشاء المتجر (provisioning status transitions)، Stripe Connect onboarding،
slug routing (تعارض slugs بين متاجر مختلفة)، theme template resolution و section data resolver
(unit tests على الـservices مباشرة + feature test على الـrendering endpoint).
```

### Worker 5 — Cms / Marketing / Blog / Lead
```
النطاق: tests/Feature/Cms/*, tests/Feature/BlogModuleTest.php, tests/Feature/Lead/*.

غطّي: marketing page DTO validation، section types المسموحة، دورة حياة lead (public submission
→ admin management → status transitions)، صلاحيات الوصول لكل حالة.
```

### Worker 6 — Catalog (Admin/Product + بناء جديد)
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

### Worker 7 — Commerce Core (بناء جديد بالكامل — أهم worker)
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

### Worker 8 — Cross-cutting
```
النطاق: tests/Unit/Policies/* (الباقي بعد ما ياخذ Worker 1 اللي يخصه)، tests/Unit/Support/*,
tests/Feature/Observability/*, tests/Feature/ApiContractTest.php, tests/Feature/FrontendContractTest.php,
tests/Feature/ExceptionRenderingTest.php.

ركّز على: كل الـpolicies (unit tests مباشرة على الـPolicy class، مو عبر HTTP)، audit logging
(هل يسجل فعلاً كل action حساس؟)، request trace/correlation ID، شكل الأخطاء الموحّد (ErrorCode enum)
عبر كل أنواع الاستثناءات.
```

---

## 7) خلاصة عملية

1. حط `phpunit.xml` بالجذر، شغّل `php artisan test` مرة.
2. راجع قائمة الحذف بقسم 2-أ بنفسك (30 ثانية كافية، git blame إذا حاب تتأكد أكثر)، احذفها.
3. أعطِ كل worker: القواعد المشتركة (قسم 5) + الـprompt الخاص فيه (قسم 6) + وصول لنفس نسخة الكود.
4. خليهم يشتغلوا بالتوازي (جلسات منفصلة).
5. جلسة أخيرة تدمج وتشغّل الكل مرة وحدة.
