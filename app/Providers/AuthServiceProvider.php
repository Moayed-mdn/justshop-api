<?php
namespace App\Providers;

use App\Models\Address;
use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Lead;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Store;
use App\Models\Tag;
use App\Policies\AddressPolicy;
use App\Policies\BlogPostPolicy;
use App\Policies\BrandPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\DashboardPolicy;
use App\Policies\LeadPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentMethodPolicy;
use App\Policies\StorePolicy;
use App\Policies\TagPolicy;
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
        Lead::class => LeadPolicy::class,
        Order::class => OrderPolicy::class,
        PaymentMethod::class => PaymentMethodPolicy::class,
        Store::class => StorePolicy::class,
        Tag::class => TagPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
}
