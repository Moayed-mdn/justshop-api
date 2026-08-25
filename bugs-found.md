# تقرير مشاكل — justshop-api

> **ملاحظة للقارئ (Cursor):** كل مشكلة بهالتقرير موجودة **بكود التطبيق نفسه** (app/...)،
> مو بملفات الـ tests. ملفات الـ tests (اللي أضيفت بمهمة QA منفصلة) بترصد/بتوثق
> هالمشاكل فقط — بعضها كتب Assertions للسلوك **الصحيح المتوقع**، وبالتالي
> **بيفشل عمداً** ضد الكود الحالي كإثبات لوجود المشكلة. الإصلاح المطلوب هون
> هو بملفات الـ app، مو بملفات الـ test.

---

## 🔴 حرجة (أمان / IDOR)

### 1. Cart items — لا يوجد تحقق من ملكية المستخدم
**الملفات:**
- `app/Actions/Cart/RemoveCartItemAction.php`
- `app/Actions/Cart/UpdateCartItemAction.php`
- `app/Repositories/Cart/CartItemRepository.php` (`findById()`)

**المشكلة:** `findById()` بيجيب الـ `CartItem` بالـ ID بس، بدون أي scoping. الـ Action بعدين
بيتحقق بس من `$item->cart->store_id === $dto->storeId`، وما بيتحقق **أبداً** من إنو
الـ cart تبع المستخدم الحالي. الـ DTO أصلاً فيه `userId` بس ما بينستخدم بالتحقق.

**الأثر:** أي زبون مسجّل بنفس المتجر فيه يحذف/يعدّل quantity لعنصر cart تبع زبون تاني،
بس لو خمّن/زاد الـ `itemId`.

**الإصلاح المطلوب:** بالـ Action، بعد ما نجيب `$item`، لازم نتحقق كمان:
```php
if ($item->cart->user_id !== $dto->userId) {
    throw new UnauthorizedStoreAccessException(...); // أو 403 مشابه
}
```

---

### 2. Store Assets — لا يوجد tenant isolation بين المتاجر
**الملف:** `app/Http/Controllers/Api/Merchant/Asset/StoreAssetController.php`
(الطرق `update()` و `destroy()`)
**الملف المرتبط:** `routes/api/v1/merchant/theme.php` (بلوك `assets` — السطر ~173)

**المشكلة:** الـ authorize بيتحقق بس من `[ThemePolicy::class, $store]` (يعني: هل عند
المستخدم صلاحية THEME_UPDATE/THEME_DELETE **على الـ store يلي بالـ URL**)، بدون أي
تحقق إنو الـ `$asset->store_id === $store->id`. بالإضافة، الـ route group تبع الـ
assets **ما فيه `->scopeBindings()`** (بعكس الـ `themes` group جنبه يلي فيه)، فالـ
`{asset}` route model binding بيصير global.

**الأثر:** merchant/store_admin عنده صلاحية على Store A فيه يحذف أو يعدّل asset
تبع Store B تماماً، بس لو حط asset id تبع Store B بالـ URL مع store id تبعه هو.

**الإصلاح المطلوب:**
- إضافة `->scopeBindings()` على الـ `Route::prefix('assets')->group(...)` بملف الـ routes، **أو**
- تحقق يدوي بالـ controller: `abort_unless($asset->store_id === $store->id, 404);`

---

## 🟠 Bug منطقي مؤكد

### 3. PaymentMethodService::deletePaymentMethod() — ما بيعيّن default جديد
**الملف:** `app/Services/PaymentMethodService.php`

**المشكلة:**
```php
public function deletePaymentMethod(PaymentMethod $paymentMethod): void
{
    if ($paymentMethod->is_default) {
        $newDefault = $this->paymentMethodRepository->getDefault($paymentMethod->user_id);
        if (!$newDefault) {  // ⚠️ هون المشكلة
            ...
        }
    }
    $this->paymentMethodRepository->delete($paymentMethod);
}
```
`getDefault()` بيسأل `WHERE is_default = true` **قبل** ما ينحذف `$paymentMethod`، وهو
لسا `is_default = true`. يعني `getDefault()` بيرجّع **نفس السجل يلي رح ينحذف**، فـ
`$newDefault` منيح أبداً null، وشرط الـ "عيّن default جديد" ما بيتنفذ إطلاقاً.

**النتيجة:** لما تحذف الـ default payment method وعندك methods تانية، ما حدا بيصير
default — رغم إنو هاد بوضوح مش السلوك المقصود (قارن مع `AddressService::deleteAddress()`
يلي بيعمل نفس المنطق **صح**: بيجيب الـ candidate الجديد **قبل** الحذف، بستثنائه بالـ query).

**الإصلاح المطلوب:** تصحيح الـ order أو الـ query، بنفس نمط `AddressService`:
```php
$nextInLine = $this->paymentMethodRepository->getUserPaymentMethods($paymentMethod->user_id)
    ->where('id', '!=', $paymentMethod->id)
    ->first();
if ($nextInLine) {
    $this->paymentMethodRepository->setAsDefault($paymentMethod->user_id, $nextInLine->id);
}
```
(هاد أصلاً موجود بالكود كـ fallback جوا الـ `if (!$newDefault)`، بس السطر السابق
بيمنعه يوصل إله.)

---

## 🟡 Dead / orphaned code (مش bug مباشر، بس يستاهل قرار)

### 4. كامل PaymentMethod domain مش موصول بأي route
**الملفات:** `app/Actions/PaymentMethod/*`, `app/DTOs/PaymentMethod/*`,
`app/Http/Requests/PaymentMethod/*`, `app/Policies/PaymentMethodPolicy.php`

**المشكلة:** الطبقة كاملة (Action/DTO/Service/Repository/Policy/FormRequest/Resource)
مبنية ومكتملة منطقياً، بس **ما فيه أي controller أو route** يستخدمها (تأكدت: لا HTTP
route ولا GraphQL field). تعليقات بالكود نفسها بتقول:
```php
// Wave 2 Remediation: Authorization removed from Action
// Authorization now explicitly owned by PaymentMethodPolicy::update() in controller
```
يعني كان فيه controller مخطط له وما اتعمل (أو انحذف).

**كمان:** `PaymentMethodPolicy` بتطلب Spatie permission (`PAYMENT_METHOD_UPDATE`/`DELETE`)
حتى للمستخدم يدير payment method تبعه هو شخصياً — هاد غير متسق مع `AddressPolicy` (يلي
بيتحقق بس من ownership، بدون أي permission مطلوب). مؤشر إضافي إنو التصميم مش نهائي.

**القرار المطلوب:** إما (أ) إكمال الـ controller/routes المفقودة، أو (ب) حذف الطبقة
كلها لو مش مخطط نستخدمها.

---

### 5. CreateOrderAction مش موصول بأي route
**الملف:** `app/Actions/Order/CreateOrderAction.php`

**المشكلة:** الـ action هاد (يلي المفروض يحوّل cart لـ order) مش مستخدم بأي مكان.
الـ flow الحقيقي يلي بيمشي فعلياً هو `app/Services/EnhancedCheckoutService.php`
(`createPaymentIntent()` + `completeCheckout()`) عبر `CheckoutController`.

**القرار المطلوب:** حذف `CreateOrderAction` (وربما `CreateOrderDTO` المرتبط) لو فعلاً
مش مستخدم، تجنباً للـ confusion المستقبلي بين الـ flow القديم والجديد.

---

## 🔵 سلوك حقيقي يستاهل مراجعة (مش bug بالضرورة)

### 6. Stock oversell عند completeCheckout
**الملف:** `app/Services/EnhancedCheckoutService.php` (`completeCheckout()`)

```php
$newQuantity = max(0, $variant->quantity - $item->quantity);
$variant->update(['quantity' => $newQuantity]);
```
ما فيه إعادة تحقق من توفر الـ stock وقت إتمام الدفع (بس وقت الإضافة للـ cart). لو
الـ stock نقص بين وقت إنشاء الـ order (`createPaymentIntent`) ووقت الدفع الفعلي
(`completeCheckout`)، الكود بيعمل clamp لـ 0 بدل ما يرفض/يعلم حدا. ممكن يسبب oversell.

### 7. OrderRepository::cancel() بيحط payment_status = REFUNDED حتى لأوردر ما انطلبش
**الملف:** `app/Repositories/Order/OrderRepository.php` (`cancel()`)

```php
public function cancel(Order $order): Order
{
    $order->update([
        'status' => OrderStatusEnum::CANCELLED,
        'payment_status' => PaymentStatusEnum::REFUNDED, // ⚠️ حتى لو أصلاً PENDING
    ]);
    ...
}
```
بيتنفذ بغض النظر عن كون الـ order كان مدفوع فعلاً أو لا (`CancelOrderAction` بيفرّق
بالمنطق قبلها هل يسوي Stripe refund فعلي أو لا، بس بالنهاية بينادي نفس `cancel()`
يلي دايماً بيحط REFUNDED). ممكن يكون مقصود (تبسيط)، بس يستاهل توضيح/تسمية أدق
(مثلاً حالة منفصلة زي `NOT_APPLICABLE` للأوردرات يلي ما انطلبتش أصلاً).

---

## ملخص سريع (للمراجعة السريعة)

| # | الخطورة | الملف | المشكلة بجملة وحدة |
|---|---|---|---|
| 1 | 🔴 حرج | `RemoveCartItemAction`/`UpdateCartItemAction` | IDOR — حذف/تعديل cart item لمستخدم تاني |
| 2 | 🔴 حرج | `StoreAssetController` + `theme.php` routes | Cross-tenant — حذف/تعديل asset لمتجر تاني |
| 3 | 🟠 bug | `PaymentMethodService::deletePaymentMethod` | حذف الـ default ما بيعيّن default جديد |
| 4 | 🟡 orphaned | `app/Actions/PaymentMethod/*` + Policy | كود كامل بدون route يوصله |
| 5 | 🟡 orphaned | `CreateOrderAction` | كود ميت، الـ flow الحقيقي مكان تاني |
| 6 | 🔵 مراجعة | `EnhancedCheckoutService::completeCheckout` | ممكن oversell عند نقص الـ stock |
| 7 | 🔵 مراجعة | `OrderRepository::cancel` | REFUNDED حتى لأوردر ما دفعش |