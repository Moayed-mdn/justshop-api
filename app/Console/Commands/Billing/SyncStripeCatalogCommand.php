<?php

namespace App\Console\Commands\Billing;

use App\Enums\Subscription\BillingCycleEnum;
use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Console\Command;
use Stripe\StripeClient;

class SyncStripeCatalogCommand extends Command
{
    protected $signature = 'billing:sync-stripe-catalog';
    protected $description = 'Sync local plans and prices to Stripe catalog';

    public function handle(StripeClient $stripe)
    {
        $this->info('Starting Stripe catalog sync...');

        $plans = Plan::active()->get();

        foreach ($plans as $plan) {
            $this->info("Processing plan: {$plan->code}");

            // Create or get Stripe product
            $stripeProduct = $this->getOrCreateStripeProduct($stripe, $plan);

            $this->info("  Stripe product ID: {$stripeProduct->id}");

            // Sync prices
            foreach ($plan->prices as $price) {
                $this->info("  Processing price: {$price->billing_cycle->value} - {$price->currency}");

                $stripePrice = $this->getOrCreateStripePrice($stripe, $stripeProduct, $price);

                $this->info("    Stripe price ID: {$stripePrice->id}");

                // Update local price with Stripe ID
                $price->update(['provider_price_id' => $stripePrice->id]);
            }
        }

        $this->info('Stripe catalog sync complete!');
    }

    private function getOrCreateStripeProduct(StripeClient $stripe, Plan $plan)
    {
        // Check if we already have a product (we don't store product ID, so search by metadata or name)
        $products = $stripe->products->all(['active' => true, 'limit' => 100]);

        foreach ($products->data as $product) {
            if (($product->metadata['plan_code'] ?? null) === $plan->code) {
                return $product;
            }
        }

        // Create new product
        return $stripe->products->create([
            'name' => is_array($plan->name) ? ($plan->name['en'] ?? reset($plan->name)) : $plan->name,
            'description' => is_array($plan->description) ? ($plan->description['en'] ?? reset($plan->description)) : $plan->description ?? null,
            'metadata' => [
                'plan_code' => $plan->code,
                'plan_tier' => $plan->tier->value,
            ],
        ]);
    }

    private function getOrCreateStripePrice(StripeClient $stripe, \Stripe\Product $product, PlanPrice $planPrice)
    {
        // Check if we already have this price
        if ($planPrice->provider_price_id) {
            try {
                return $stripe->prices->retrieve($planPrice->provider_price_id);
            } catch (\Exception $e) {
                $this->warn("    Existing price ID not found, creating new one...");
            }
        }

        // Create new price
        $interval = $planPrice->billing_cycle === BillingCycleEnum::MONTHLY ? 'month' : 'year';

        return $stripe->prices->create([
            'product' => $product->id,
            'unit_amount' => $planPrice->amount_cents,
            'currency' => strtolower($planPrice->currency),
            'recurring' => ['interval' => $interval],
            'metadata' => [
                'plan_code' => $planPrice->plan->code,
                'billing_cycle' => $planPrice->billing_cycle->value,
            ],
        ]);
    }
}
