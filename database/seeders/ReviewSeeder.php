<?php
// database/seeders/ReviewSeeder.php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\Concerns\SeedsDemoStore;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    use SeedsDemoStore;

    public function run(): void
    {
        $storeId = $this->demoStoreId();
        $users    = User::all();
        $products = Product::query()->where('store_id', $storeId)->get();

        if ($users->isEmpty() || $products->isEmpty()) {
            $this->command->info('❌ Need users and products seeded first!');
            return;
        }

        $comments = [
            5 => ['Excellent product!', 'Amazing quality, highly recommend!', 'Best purchase ever!', 'Perfect, exactly as described.'],
            4 => ['Very good, minor issues.', 'Great value for money.', 'Happy with the purchase.', 'Would buy again.'],
            3 => ['Decent, nothing special.', 'Average quality.', 'It is okay for the price.', 'Expected better.'],
            2 => ['Not great, some defects.', 'Below expectations.', 'Quality could be improved.'],
            1 => ['Very disappointed.', 'Would not recommend.', 'Poor quality.'],
        ];

        foreach ($products as $product) {
            $reviewCount = rand(3, min(10, $users->count()));
            $reviewers   = $users->random($reviewCount);

            foreach ($reviewers as $user) {
                // Weighted random — more 4s and 5s than 1s
                $rating = $this->weightedRating();

                Review::create([
                    'user_id'     => $user->id,
                    'product_id'  => $product->id,
                    'rating'      => $rating,
                    'comment'     => $comments[$rating][array_rand($comments[$rating])],
                    'is_approved' => true,
                ]);
            }
        }

        $this->command->info('✅ Reviews seeded!');
    }

    /**
     * Weighted: 5★ → 35%, 4★ → 30%, 3★ → 20%, 2★ → 10%, 1★ → 5%
     */
    private function weightedRating(): int
    {
        $rand = rand(1, 100);

        return match (true) {
            $rand <= 5  => 1,
            $rand <= 15 => 2,
            $rand <= 35 => 3,
            $rand <= 65 => 4,
            default     => 5,
        };
    }
}