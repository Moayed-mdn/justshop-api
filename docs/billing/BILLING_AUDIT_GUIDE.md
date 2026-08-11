# 🔍 دليل تدقيق الفوترة - ضمان عدم الغش من أي طرف

## الهدف
التأكد من أن النظام يُحصّل المبالغ الصحيحة من العملاء ولا يسمح بخدمات مجانية غير مصرح بها، وفي نفس الوقت لا يُحمّل العميل مرتين أو بمبالغ خاطئة.

---

## ✅ الفحوصات الإلزامية

### 1️⃣ فحص الاشتراك النشط

```bash
php artisan tinker --execute="
\$sub = App\Models\Subscription::where('status', 'active')->first();
echo 'ID: ' . \$sub->id . PHP_EOL;
echo 'Provider Subscription ID: ' . (\$sub->provider_subscription_id ?? 'NULL') . PHP_EOL;
echo 'Plan: ' . \$sub->plan->code . PHP_EOL;
echo 'Price: ' . (\$sub->planPrice->amount_cents / 100) . ' USD' . PHP_EOL;
"
```

**❌ RED FLAGS:**
- `provider_subscription_id` = NULL → العميل يحصل على خدمة مجانية!
- Multiple `active` subscriptions → احتمال double charging!

---

### 2️⃣ فحص الفواتير

```bash
php audit_billing.php
```

**يجب التحقق من:**
- ✅ كل اشتراك `active` له `provider_subscription_id`
- ✅ لا توجد فواتير للاشتراكات `expired` (abandoned checkouts)
- ✅ لا توجد فواتير للاشتراكات `trialing` بمبلغ > 0
- ✅ اشتراك `active` واحد فقط لكل billing_account_id

---

### 3️⃣ فحص Proration عند الترقية/التخفيض

```bash
php audit_proration.php
```

**Proration Formula:**
```
Proration = (Price_Difference / Total_Days) × Days_Remaining
```

**مثال:**
- Upgrade من Growth (99 USD) إلى Enterprise (299 USD)
- بعد 1 يوم من شهر 30 يوم
- Proration = (299 - 99) / 30 × 29 = **193.33 USD**

**❌ RED FLAGS:**
- لا توجد فاتورة proration بعد الترقية → خسارة مالية!
- Proration amount خطأ → تحصيل غير صحيح

---

### 4️⃣ فحص التزامن مع Stripe

```sql
SELECT 
    id,
    status,
    provider_subscription_id,
    provider_synced_at,
    updated_at,
    TIMESTAMPDIFF(MINUTE, provider_synced_at, updated_at) as sync_delay_minutes
FROM subscriptions 
WHERE status = 'active';
```

**❌ RED FLAGS:**
- `sync_delay_minutes` > 10 → بيانات محلية غير متزامنة مع Stripe
- `provider_synced_at` = NULL → لم يتم التزامن أبداً!

---

## 🚨 سيناريوهات الغش المحتملة

### من طرف العميل:

#### 1. محاولة الحصول على خدمة مجانية
**الطريقة:** فتح checkout ثم إغلاقه بدون دفع (abandoned checkout)

**الحماية:**
```php
// CreateCheckoutSessionAction.php
// ✅ يُنشئ subscription بحالة 'incomplete'
// ✅ عند فتح checkout جديد، يُحوّل القديم إلى 'expired'
// ✅ 'expired' لا يظهر في scopeWithAccess()
// ✅ لا يمنح صلاحيات
```

**التحقق:**
```bash
# يجب أن يُرجع 0
php artisan tinker --execute="
echo App\Models\Subscription::where('status', 'expired')
    ->withAccess()
    ->count();
"
```

---

#### 2. استخدام الفترة التجريبية أكثر من مرة
**الطريقة:** إنشاء حساب جديد للحصول على trial جديد

**الحماية:**
```php
// StartTrialAction.php
if ($billingAccount->trial_used) {
    throw new TrialAlreadyUsedException();
}
```

**التحقق:**
```sql
SELECT 
    billing_account_id,
    COUNT(*) as trial_count
FROM subscriptions 
WHERE status = 'trialing' OR trial_ends_at IS NOT NULL
GROUP BY billing_account_id
HAVING trial_count > 1;
```
يجب أن يُرجع 0 نتائج.

---

#### 3. محاولة تجنب الدفع بعد انتهاء Trial
**الطريقة:** عدم إكمال checkout بعد انتهاء trial

**الحماية:**
```php
// ExpireStaleIncompleteSubscriptionsCommand
// يُشغل كل ساعة ويُحوّل incomplete > 24h إلى expired
```

**التحقق:**
```sql
SELECT * FROM subscriptions 
WHERE status = 'incomplete' 
AND created_at < NOW() - INTERVAL 24 HOUR;
```
يجب أن يُرجع 0 نتائج (أو أن يتم تحويلهم إلى expired قريباً).

---

### من طرف النظام (أخطاء تقنية):

#### 1. Double Charging - تحميل مرتين
**السبب:** وجود اشتراكين active في نفس الوقت

**الحماية:**
```php
// SubscriptionRepository::create()
if ($existingActive) {
    throw new RuntimeException(
        "Billing account already has an active subscription"
    );
}
```

**التحقق:**
```sql
SELECT 
    billing_account_id,
    COUNT(*) as active_count
FROM subscriptions 
WHERE status = 'active'
GROUP BY billing_account_id
HAVING active_count > 1;
```

---

#### 2. Free Service - خدمة مجانية غير مقصودة
**السبب:** subscription بحالة `active` بدون `provider_subscription_id`

**التحقق:**
```sql
SELECT * FROM subscriptions 
WHERE status = 'active' 
AND provider_subscription_id IS NULL;
```

**❌ إذا وُجدت نتائج:** العميل يحصل على خدمة مجانية!

---

#### 3. Missing Proration - عدم تحصيل فرق السعر
**السبب:** Upgrade/Downgrade بدون إنشاء فاتورة proration

**التحقق:**
```bash
php audit_proration.php
```

**الحل إذا كانت الفاتورة مفقودة:**
1. تحقق من Stripe Dashboard
2. تحقق من webhooks: `invoice.created`, `invoice.paid`
3. تحقق من `storage/logs/laravel.log`
4. أعد تشغيل webhook handler يدوياً إذا لزم الأمر

---

## 📊 Dashboard Metrics للمراقبة

### Metrics يجب مراقبتها يومياً:

```sql
-- 1. Active subscriptions without provider_subscription_id
SELECT COUNT(*) as free_riders
FROM subscriptions 
WHERE status = 'active' 
AND provider_subscription_id IS NULL;
-- Expected: 0

-- 2. Multiple active subscriptions per account
SELECT billing_account_id, COUNT(*) as count
FROM subscriptions 
WHERE status = 'active'
GROUP BY billing_account_id
HAVING count > 1;
-- Expected: 0 rows

-- 3. Expired subscriptions in withAccess scope
SELECT COUNT(*) as expired_with_access
FROM subscriptions 
WHERE status IN ('expired', 'incomplete')
AND status IN (
    SELECT DISTINCT status 
    FROM subscriptions 
    WHERE /* withAccess logic */
);
-- Expected: 0

-- 4. Stale incomplete subscriptions (> 24h)
SELECT COUNT(*) as stale_incomplete
FROM subscriptions 
WHERE status = 'incomplete' 
AND created_at < NOW() - INTERVAL 24 HOUR;
-- Expected: 0 (or will be cleaned soon)

-- 5. Sync delay (subscriptions not synced in last 10 min)
SELECT COUNT(*) as unsynced
FROM subscriptions 
WHERE status = 'active'
AND (provider_synced_at IS NULL 
     OR TIMESTAMPDIFF(MINUTE, provider_synced_at, NOW()) > 10);
-- Expected: 0

-- 6. Total MRR (Monthly Recurring Revenue)
SELECT SUM(pp.amount_cents) / 100 as MRR
FROM subscriptions s
JOIN plan_prices pp ON s.plan_price_id = pp.id
WHERE s.status = 'active'
AND pp.billing_cycle = 'monthly';

-- 7. Total ARR (Annual Recurring Revenue) 
SELECT SUM(pp.amount_cents) / 100 as ARR
FROM subscriptions s
JOIN plan_prices pp ON s.plan_price_id = pp.id
WHERE s.status = 'active'
AND pp.billing_cycle = 'annual';
```

---

## 🔧 أدوات التدقيق

### 1. فحص شامل
```bash
php audit_billing.php
```

### 2. فحص Proration
```bash
php audit_proration.php
```

### 3. فحص Webhook Logs
```bash
tail -f storage/logs/laravel.log | grep "webhook\|billing"
```

### 4. فحص Stripe Dashboard
- الذهاب إلى: https://dashboard.stripe.com/subscriptions
- مقارنة عدد الاشتراكات مع قاعدة البيانات المحلية
- التحقق من Invoices المدفوعة

---

## ⚡ الإجراءات الفورية عند اكتشاف مشكلة

### إذا وُجد free rider (active بدون provider_subscription_id):

```bash
# 1. تعطيل الاشتراك فوراً
php artisan tinker --execute="
\$sub = App\Models\Subscription::find(SUBSCRIPTION_ID);
\$stateMachine = app(App\Services\Subscription\SubscriptionStateMachine::class);
\$stateMachine->transition(
    subscription: \$sub,
    toStatus: App\Enums\Subscription\SubscriptionStatusEnum::EXPIRED,
    source: 'admin',
    reason: 'missing_provider_subscription_id'
);
"

# 2. إشعار فريق الدعم
# 3. التحقق من سبب المشكلة
# 4. إصلاح البيانات أو طلب الدفع من العميل
```

---

### إذا وُجد double charging:

```bash
# 1. إلغاء الاشتراك المكرر في Stripe
# 2. استرداد المبلغ المحصل بالخطأ
# 3. تحديث الاشتراك المحلي

php artisan tinker --execute="
\$duplicate = App\Models\Subscription::find(DUPLICATE_ID);
\$stateMachine = app(App\Services\Subscription\SubscriptionStateMachine::class);
\$stateMachine->transition(
    subscription: \$duplicate,
    toStatus: App\Enums\Subscription\SubscriptionStatusEnum::CANCELED,
    source: 'admin',
    reason: 'duplicate_subscription_cleanup'
);
"
```

---

## 📝 Checklist يومي

- [ ] تشغيل `php audit_billing.php`
- [ ] فحص Stripe Dashboard
- [ ] مراقبة webhook logs
- [ ] التحقق من MRR/ARR
- [ ] فحص stale incomplete subscriptions
- [ ] مقارنة Stripe subscriptions count مع قاعدة البيانات

---

## 🎯 الخلاصة

**✅ نظام آمن إذا:**
1. كل `active` subscription له `provider_subscription_id`
2. اشتراك `active` واحد فقط لكل billing_account
3. الاشتراكات الـ `expired` لا تظهر في `withAccess()`
4. Proration يتم تحصيله عند Upgrade/Downgrade
5. التزامن مع Stripe يحدث في غضون دقائق

**❌ مشاكل حرجة:**
1. Active subscription بدون provider_subscription_id
2. Multiple active subscriptions لنفس الحساب
3. Expired/Incomplete subscriptions تمنح صلاحيات
4. Missing proration invoices
5. Sync delay > 10 minutes
