<?php

namespace Database\Seeders;

use App\Enums\Subscription\PlanTierEnum;
use App\Enums\Subscription\BillingCycleEnum;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete existing plans (useful for re-seeding)
        Plan::query()->forceDelete();

        // Starter Plan
        $starter = Plan::create([
            'code'        => 'starter',
            'name'        => [
                'en' => 'Starter',
                'ar' => 'المبتدئ',
            ],
            'description' => [
                'en' => 'Perfect for getting started with your first online store',
                'ar' => 'مثالي للبدء بمتجرك الإلكتروني الأول',
            ],
            'tier'        => PlanTierEnum::STARTER,
            'tier_rank'   => 1,
            'is_public'   => true,
            'is_active'   => true,
            'trial_days'  => 14,
            'sort_order'  => 1,
            'metadata'    => [
                'badge'       => 'Most Popular',
                'description' => 'Everything you need to start selling online',
            ],
        ]);

        // Starter Plan Prices
        $starter->prices()->createMany([
            [
                'billing_cycle'     => BillingCycleEnum::MONTHLY,
                'currency'          => 'USD',
                'amount_cents'      => 2900, // $29.00
                'provider'          => 'stripe',
                'provider_price_id' => null, // Will be set by billing:sync-stripe-catalog command
                'is_active'         => true,
            ],
            [
                'billing_cycle'     => BillingCycleEnum::ANNUAL,
                'currency'          => 'USD',
                'amount_cents'      => 29000, // $290.00 (save $58/year)
                'provider'          => 'stripe',
                'provider_price_id' => null,
                'is_active'         => true,
            ],
        ]);

        // Starter Plan Features
        $starter->features()->createMany([
            ['feature_key' => 'stores.max', 'value_type' => 'limit', 'limit_value' => 1, 'boolean_value' => null],
            ['feature_key' => 'products.max', 'value_type' => 'limit', 'limit_value' => 1000, 'boolean_value' => null],
            ['feature_key' => 'users.max', 'value_type' => 'limit', 'limit_value' => 2, 'boolean_value' => null],
            ['feature_key' => 'analytics.advanced', 'value_type' => 'boolean', 'limit_value' => null, 'boolean_value' => false],
            ['feature_key' => 'api.access', 'value_type' => 'boolean', 'limit_value' => null, 'boolean_value' => false],
            ['feature_key' => 'custom_domain.enabled', 'value_type' => 'boolean', 'limit_value' => null, 'boolean_value' => false],
            ['feature_key' => 'support.priority', 'value_type' => 'boolean', 'limit_value' => null, 'boolean_value' => false],
            ['feature_key' => 'webhooks.enabled', 'value_type' => 'boolean', 'limit_value' => null, 'boolean_value' => false],
        ]);

        // Growth Plan
        $growth = Plan::create([
            'code'        => 'growth',
            'name'        => [
                'en' => 'Growth',
                'ar' => 'النمو',
            ],
            'description' => [
                'en' => 'Scale your business with multiple stores and advanced features',
                'ar' => 'قم بتوسيع نطاق عملك مع متاجر متعددة وميزات متقدمة',
            ],
            'tier'        => PlanTierEnum::GROWTH,
            'tier_rank'   => 2,
            'is_public'   => true,
            'is_active'   => true,
            'trial_days'  => 14,
            'sort_order'  => 2,
            'metadata'    => [
                'badge'       => 'Best Value',
                'description' => 'Advanced features for growing businesses',
            ],
        ]);

        // Growth Plan Prices
        $growth->prices()->createMany([
            [
                'billing_cycle'     => BillingCycleEnum::MONTHLY,
                'currency'          => 'USD',
                'amount_cents'      => 9900, // $99.00
                'provider'          => 'stripe',
                'provider_price_id' => null,
                'is_active'         => true,
            ],
            [
                'billing_cycle'     => BillingCycleEnum::ANNUAL,
                'currency'          => 'USD',
                'amount_cents'      => 99000, // $990.00 (save $198/year)
                'provider'          => 'stripe',
                'provider_price_id' => null,
                'is_active'         => true,
            ],
        ]);

        // Growth Plan Features
        $growth->features()->createMany([
            ['feature_key' => 'stores.max', 'value_type' => 'limit', 'limit_value' => 3, 'boolean_value' => null],
            ['feature_key' => 'products.max', 'value_type' => 'unlimited', 'limit_value' => null, 'boolean_value' => null],
            ['feature_key' => 'users.max', 'value_type' => 'limit', 'limit_value' => 10, 'boolean_value' => null],
            ['feature_key' => 'analytics.advanced', 'value_type' => 'boolean', 'limit_value' => null, 'boolean_value' => true],
            ['feature_key' => 'api.access', 'value_type' => 'boolean', 'limit_value' => null, 'boolean_value' => true],
            ['feature_key' => 'custom_domain.enabled', 'value_type' => 'boolean', 'limit_value' => null, 'boolean_value' => true],
            ['feature_key' => 'support.priority', 'value_type' => 'boolean', 'limit_value' => null, 'boolean_value' => false],
            ['feature_key' => 'webhooks.enabled', 'value_type' => 'boolean', 'limit_value' => null, 'boolean_value' => true],
        ]);

        // Enterprise Plan
        $enterprise = Plan::create([
            'code'        => 'enterprise',
            'name'        => [
                'en' => 'Enterprise',
                'ar' => 'المؤسسات',
            ],
            'description' => [
                'en' => 'Unlimited stores and priority support for large businesses',
                'ar' => 'متاجر غير محدودة ودعم ذو أولوية للشركات الكبيرة',
            ],
            'tier'        => PlanTierEnum::ENTERPRISE,
            'tier_rank'   => 3,
            'is_public'   => true,
            'is_active'   => true,
            'trial_days'  => 14,
            'sort_order'  => 3,
            'metadata'    => [
                'badge'       => 'Unlimited',
                'description' => 'Everything you need for large-scale operations',
            ],
        ]);

        // Enterprise Plan Prices
        $enterprise->prices()->createMany([
            [
                'billing_cycle'     => BillingCycleEnum::MONTHLY,
                'currency'          => 'USD',
                'amount_cents'      => 29900, // $299.00
                'provider'          => 'stripe',
                'provider_price_id' => null,
                'is_active'         => true,
            ],
            [
                'billing_cycle'     => BillingCycleEnum::ANNUAL,
                'currency'          => 'USD',
                'amount_cents'      => 299000, // $2,990.00 (save $598/year)
                'provider'          => 'stripe',
                'provider_price_id' => null,
                'is_active'         => true,
            ],
        ]);

        // Enterprise Plan Features
        $enterprise->features()->createMany([
            ['feature_key' => 'stores.max', 'value_type' => 'unlimited', 'limit_value' => null, 'boolean_value' => null],
            ['feature_key' => 'products.max', 'value_type' => 'unlimited', 'limit_value' => null, 'boolean_value' => null],
            ['feature_key' => 'users.max', 'value_type' => 'unlimited', 'limit_value' => null, 'boolean_value' => null],
            ['feature_key' => 'analytics.advanced', 'value_type' => 'boolean', 'limit_value' => null, 'boolean_value' => true],
            ['feature_key' => 'api.access', 'value_type' => 'boolean', 'limit_value' => null, 'boolean_value' => true],
            ['feature_key' => 'custom_domain.enabled', 'value_type' => 'boolean', 'limit_value' => null, 'boolean_value' => true],
            ['feature_key' => 'support.priority', 'value_type' => 'boolean', 'limit_value' => null, 'boolean_value' => true],
            ['feature_key' => 'webhooks.enabled', 'value_type' => 'boolean', 'limit_value' => null, 'boolean_value' => true],
        ]);

        $this->command->info('✅ Created 3 plans with prices and features');
        $this->command->info('   - Starter: $29/mo, 1 store, 1000 products');
        $this->command->info('   - Growth: $99/mo, 3 stores, unlimited products');
        $this->command->info('   - Enterprise: $299/mo, unlimited everything');
    }
}

