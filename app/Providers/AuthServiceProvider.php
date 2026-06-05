<?php
namespace App\Providers;

use App\Models\Address;
use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HeroBanner;
use App\Models\Lead;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Store;
use App\Models\Tag;
use App\Models\User;
use App\Policies\AddressPolicy;
use App\Policies\BlogPostPolicy;
use App\Policies\BrandPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\DashboardPolicy;
use App\Policies\HeroBannerPolicy;
use App\Policies\LeadPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentMethodPolicy;
use App\Policies\StorePolicy;
use App\Policies\TagPolicy;
use App\Models\Cms\Marketing\Platform\PlatformMarketingPage;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\Cms\MarketingPage;
use App\Policies\Cms\Marketing\Platform\PlatformMarketingPagePolicy;
use App\Policies\Cms\Marketing\Store\StoreMarketingPagePolicy;
use App\Policies\MarketingPagePolicy;
use App\Support\Auth\DashboardAuthorization;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Address::class => AddressPolicy::class,
        BlogPost::class => BlogPostPolicy::class,
        Brand::class => BrandPolicy::class,
        Category::class => CategoryPolicy::class,
        DashboardAuthorization::class => DashboardPolicy::class,
        HeroBanner::class => HeroBannerPolicy::class,
        Lead::class => LeadPolicy::class,
        Order::class => OrderPolicy::class,
        PaymentMethod::class => PaymentMethodPolicy::class,
        Store::class => StorePolicy::class,
        Tag::class => TagPolicy::class,
        User::class => \App\Policies\MembershipPolicy::class,
        MarketingPage::class => MarketingPagePolicy::class,
        PlatformMarketingPage::class => PlatformMarketingPagePolicy::class,
        StoreMarketingPage::class => StoreMarketingPagePolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
}
