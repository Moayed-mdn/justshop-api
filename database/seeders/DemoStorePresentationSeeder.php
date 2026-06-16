<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Image;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Database\Seeders\Concerns\SeedsDemoStore;
use Illuminate\Database\Seeder;

class DemoStorePresentationSeeder extends Seeder
{
    use SeedsDemoStore;

    public function run(): void
    {
        $store = $this->demoStore();

        $store->update([
            'name' => 'JustShop Demo',
            'currency' => 'USD',
        ]);

        $updatedImages = 0;
        $attachedImages = 0;

        Product::query()
            ->where('store_id', $store->id)
            ->with(['images', 'category.translations'])
            ->orderBy('id')
            ->chunkById(50, function ($products) use (&$updatedImages, &$attachedImages): void {
                foreach ($products as $product) {
                    $imageUrl = $this->catalogImageUrl($product);

                    $primary = $product->images->firstWhere('is_primary', true)
                        ?? $product->images->first();

                    if ($primary instanceof Image) {
                        if ($this->isPlaceholderImage((string) $primary->image_url)) {
                            $primary->update(['image_url' => $imageUrl]);
                            $updatedImages++;
                        }
                        continue;
                    }

                    $product->images()->create([
                        'image_url' => $imageUrl,
                        'alt_text' => $product->translation('en')?->name ?? 'Product image',
                        'is_primary' => true,
                        'sort_order' => 0,
                    ]);
                    $attachedImages++;

                    $variant = $product->primaryVariant();
                    if ($variant instanceof ProductVariant) {
                        $variantImage = $variant->images()->where('is_primary', true)->first();
                        if ($variantImage instanceof Image && $this->isPlaceholderImage((string) $variantImage->image_url)) {
                            $variantImage->update(['image_url' => $imageUrl]);
                        }
                    }
                }
            });

        $this->command?->info(sprintf(
            'DemoStorePresentationSeeder: store branded as JustShop Demo; %d product images attached, %d placeholders upgraded.',
            $attachedImages,
            $updatedImages,
        ));
    }

    private function catalogImageUrl(Product $product): string
    {
        $categorySlug = (string) ($product->category?->translation('en')?->slug
            ?? $product->category?->slug
            ?? 'catalog');

        $topic = match (true) {
            str_contains($categorySlug, 'phone') => 'technology',
            str_contains($categorySlug, 'laptop') => 'computer',
            str_contains($categorySlug, 'accessor') => 'accessory',
            str_contains($categorySlug, 'shoe'), str_contains($categorySlug, 'sport') => 'sneakers',
            str_contains($categorySlug, 'cloth'), str_contains($categorySlug, 'fashion') => 'fashion',
            str_contains($categorySlug, 'furniture'), str_contains($categorySlug, 'appliance') => 'interior',
            str_contains($categorySlug, 'skin'), str_contains($categorySlug, 'beauty') => 'cosmetic',
            default => 'retail',
        };

        return sprintf(
            'https://picsum.photos/seed/justshop-%s-%d/800/800',
            $topic,
            $product->id,
        );
    }

    private function isPlaceholderImage(string $url): bool
    {
        return $url === ''
            || str_contains($url, 'default.png')
            || str_contains($url, 'placehold.co');
    }
}
