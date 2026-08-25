# Worker 8 — Cross-cutting: تقرير التسليم

## ⚠️ أهم شي قبل أي شي: مجلد `config/` مفقود بالكامل من الملف اللي رفعته

فحصت الـzip الأصلي نفسه (مو بس النسخة اللي فككتها) — **صفر ملف تحت `config/`** بكل المشروع.
هذا يعني حرفياً التطبيق ما يقدر يبوت (`config/app.php` فيه `APP_KEY` اللي الـencryption
provider يحتاجه إجباري، `config/database.php` فيه تعريف اتصال sqlite اللي `phpunit.xml`
المرفق سابقاً يعتمد عليه). بما إن الـ78 test الموجودين أصلاً مكتوبين بافتراض تطبيق شغال،
شبه مؤكد هذا **مشكلة بعملية التصدير/الضغط** (أداة الـzip تجاهلت `config/`)، مو إن مشروعك
الحقيقي بدون config فعلاً — بس **لازم تتأكد وتعيد الرفع** قبل ما حد يقدر يشغّل أي test
من اللي بالأسفل. ما لمست هالموضوع (مو من نطاقي)، بس هذا blocker حقيقي لازم ينحل أول.

**قيد مهم:** ما كان عندي `composer`/`vendor` بالبيئة (ولا وصول لـpackagist)، فكل الشغل
بالأسفل تحليل ثابت (قراءة الكود الفعلي بعناية) — **مو تنفيذ فعلي لـ`php artisan test`**.
لازم تشغلهم فعلياً بعد ما تصلح `config/` للتأكد 100%.

---

## شنو تسلمته (31 ملف، بالمرفقات: `worker8-changes.zip`)

### تدقيق (Patch) — 4 ملفات موجودة، أضفت الفروع الناقصة بس:
- `tests/Unit/Policies/NavigationPolicyTest.php`
- `tests/Unit/Policies/ShippingPolicyTest.php`
- `tests/Unit/Policies/ThemePolicyTest.php`
- `tests/Unit/Policies/SystemTemplatePolicyTest.php`

كلهم كانوا يغطّون فقط بعض الفروع (مثلاً `view` بس)، وما كانوا يغطّون non-member/
admin-without-permission/super-admin لباقي الـabilities (create/update/delete/publish).
أضفت الاختبارات الناقصة فقط، ما مسحت شي موجود.

### بناء جديد بالكامل — 26 policy كان عندها صفر test:
Brand, Category, Tag, Product, Order, Store, Membership, Dashboard, Lead, Address,
PaymentMethod, Profile, PlatformOrder, PageTemplate, CmsDocument, BlogPost,
StoreMarketingPage, PlatformMarketingPage, MarketingPage (legacy — شوف الملاحظة أدناه)،
+ Platform (AuditLog/FeatureFlag/PlatformAnalytics بملف وحد لأن الشكل مطابق تماماً)،
+ Billing (BillingPortal, Checkout, Invoice, Subscription).

### تدقيق لباقي نطاق Worker 8:
- `tests/Unit/Support/FrontendUrlBuilderTest.php` — أضفت 3 tests (كان فاضي من اختبار
  حماية الـopen-redirect الموجودة فعلياً بالكود).
- `tests/Feature/Observability/AuditLoggerTest.php` — أضفت test يوثّق finding (تحت).
- `tests/Feature/Observability/RequestTraceContextTest.php` — قريتها كاملة، سليمة، **ما عدّلت شي**.
- `tests/Feature/ExceptionRenderingTest.php` — أعدت كتابتها بالكامل (كانت 3 tests سطحية،
  صارت 8 تغطي `code` field وheader الـcorrelation، شوف finding مهم بالأسفل).
- `tests/Feature/ApiContractTest.php`, `tests/Feature/FrontendContractTest.php` — راجعتهم
  (583 + 301 سطر)، فحصت خصوصاً الـ2 error-normalization tests ضد الـhandler الحقيقي —
  **مطابقين تماماً، سليمين، ما عدّلت شي**.

---

## Findings حقيقية لقيتها بالكود (مو مشاكل بالـtests، مشاكل بالتطبيق)

1. **`App\Models\Cms\MarketingPage` بدون migration إطلاقاً** — الـRepository وكل
   الـActions (Create/Update/Publish/Get/ResolveBySlug) وcontroller يستخدمونه، وفيه
   command صريح (`MigratePlatformMarketingCommand`) يسميه "the legacy system" اللي
   لازم ينترحل منه لـ`platform_marketing_pages`. على أي تنصيب جديد (أو بيئة الاختبار)
   هذا يرمي `QueryException`. كتبت test صريح يثبت المشكلة بدل ما يخفيها.

2. **`StorePolicy::restore()`/`forceDelete()` ما فيهم مسار وحد يرجع `true`** — حتى
   المالك نفسه ما يقدر يسترجع متجر محذوف عبر هذا الـpolicy. ممكن مقصود (يصير بس عبر
   دعم فني منفصل)، بس يستاهل تأكيد.

3. **`PaymentMethodPolicy`/`ProfilePolicy` يتطلبوا permission صريح حتى للوصول لموردك
   الخاص** — عميل عادي ما يقدر يعدّل بطاقته أو بروفايله الخاص إلا إذا عنده الـpermission
   بالتحديد ممنوح له. تأكد إن الدور الافتراضي للعميل يمنحه هالـpermissions.

4. **`OrderPolicy` ما فيه استثناء super_admin** (بخلاف كل الـpolicies الثانية) — أدمن
   منصة ما يوصل لطلب إلا عبر impersonation فعّال.

5. **تفعيل الـimpersonation ما يسجّل بجدول `audit_logs`** — يسجّل بس بملف الـlog
   (`ImpersonationTelemetry` → `Log::warning`)، يعني شاشة "Audit Logs" (`AuditLogPolicy`)
   ما رح تعرض أي شي عن جلسات الـimpersonation — أحد أخطر الصلاحيات بالنظام.

6. **404 العام (route مو موجود) وrجع نفس كود الخطأ (`STR_001`) متل "store not found"
   الحقيقي** — ممكن يصير لبس عند أي client يفرّق بين الحالتين بالـ`code`.

---

## الأسئلة المفتوحة (مو قراري أحسمها)

- هل `restore`/`forceDelete` بـ`StorePolicy` مقصودين "معطّلين بالكامل" فعلاً؟
- هل `PAYMENT_METHOD_UPDATE`/`PROFILE_*` permissions ممنوحة تلقائياً بدور العميل الافتراضي؟
- هل `MarketingPage` (القديم) لازم يترحل ويتحذف، ولا لازم يرجع الـmigration المفقودة؟
- هل تفعيل impersonation المفروض يسجل بـ`audit_logs` كمان مو بس بملف الـlog؟

---

## اللي ما سويته من نطاق Worker 8 الأصلي

كل شي بالنطاق تغطى (policies + Support + Observability + ApiContract/FrontendContract/
ExceptionRendering). ما فيه شي متبقي من القائمة الأصلية.
