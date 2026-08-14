<?php
namespace App\Providers;

use App\Models\Address;
use App\Models\BillingAccount;
use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Cms\CmsDocument;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Order;
use App\Models\PageTemplate;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\Theme\ThemeTemplate;
use App\Models\User;
use App\Policies\AddressPolicy;
use App\Policies\Billing\BillingPortalPolicy;
use App\Policies\Billing\CheckoutPolicy;
use App\Policies\Billing\InvoicePolicy;
use App\Policies\Billing\SubscriptionPolicy;
use App\Policies\BlogPostPolicy;
use App\Policies\BrandPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CmsDocumentPolicy;
use App\Policies\DashboardPolicy;
use App\Policies\LeadPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PageTemplatePolicy;
use App\Policies\PaymentMethodPolicy;
use App\Policies\PlatformOrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\StorePolicy;
use App\Policies\NavigationPolicy;
use App\Policies\ShippingPolicy;
use App\Policies\TagPolicy;
use App\Policies\ThemePolicy;
use App\Models\Cms\Marketing\Platform\PlatformMarketingPage;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\Cms\MarketingPage;
use App\Policies\Cms\Marketing\Platform\PlatformMarketingPagePolicy;
use App\Policies\Cms\Marketing\Store\StoreMarketingPagePolicy;
use App\Policies\MarketingPagePolicy;
use App\Policies\Theme\SystemTemplatePolicy;
use App\Policies\ProfilePolicy;
use App\Support\Auth\DashboardAuthorization;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Address::class => AddressPolicy::class,
        BlogPost::class => BlogPostPolicy::class,
        Brand::class => BrandPolicy::class,
        Category::class => CategoryPolicy::class,
        CmsDocument::class => CmsDocumentPolicy::class,
        DashboardAuthorization::class => DashboardPolicy::class,
        Lead::class => LeadPolicy::class,
        Order::class => OrderPolicy::class,
        PageTemplate::class => PageTemplatePolicy::class,
        PaymentMethod::class => PaymentMethodPolicy::class,
        Product::class => ProductPolicy::class,
        Store::class => StorePolicy::class,
        Tag::class => TagPolicy::class,
        User::class => \App\Policies\MembershipPolicy::class,
        MarketingPage::class => MarketingPagePolicy::class,
        PlatformMarketingPage::class => PlatformMarketingPagePolicy::class,
        StoreMarketingPage::class => StoreMarketingPagePolicy::class,
        ThemeTemplate::class => SystemTemplatePolicy::class,
        // Class-based policies (used with [PolicyClass::class, $store])
        CmsDocumentPolicy::class => CmsDocumentPolicy::class,
        NavigationPolicy::class => NavigationPolicy::class,
        PageTemplatePolicy::class => PageTemplatePolicy::class,
        ProductPolicy::class => ProductPolicy::class,
        ShippingPolicy::class => ShippingPolicy::class,
        StoreMarketingPagePolicy::class => StoreMarketingPagePolicy::class,
        ThemePolicy::class => ThemePolicy::class,
        SubscriptionPolicy::class => SubscriptionPolicy::class,
        InvoicePolicy::class => InvoicePolicy::class,
        BillingPortalPolicy::class => BillingPortalPolicy::class,
        CheckoutPolicy::class => CheckoutPolicy::class,
        OrderPolicy::class => OrderPolicy::class,
        PlatformOrderPolicy::class => PlatformOrderPolicy::class,
        ProfilePolicy::class => ProfilePolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
}
