<?php

namespace Database\Seeders;

use App\Models\HeroBanner;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HeroBannerSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $store = Store::query()->where('slug', 'merchant-store')->first();

        if (!$store) {
            $this->command?->warn('HeroBannerSeeder skipped because the default merchant store is missing.');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Banner 0 - Image
        |--------------------------------------------------------------------------
        */
        $banner0 = HeroBanner::create([
            'store_id'      => $store->id,
            'cat_url'       => '/shop',
            'position'      => 0,
            'visual_type'   => 'image',
            'image_path'    => 'hero/hero-banner.jpg',
            'gradient_from' => null,
            'gradient_to'   => null,
            'is_active'     => true,
            'starts_at'     => $now,
            'ends_at'       => null,
        ]);

        $banner0->translations()->createMany([
            [
                'locale'    => 'en',
                'title'     => 'Your private world of luxury shopping.',
                'subtitle'  => 'Enjoy moments of calm while choosing your favorite pieces; we provide everything you need for an unforgettable journey.',
                'cta_text'  => 'Shop Now',
            ],
            [
                'locale' => 'ar',
                'title' => 'عالمك الخاص من التسوق الفاخر',
                'subtitle' => 'استمتع بلحظات من الهدوء أثناء اختيار قطعك المفضلة؛ نحن نوفر كل ما تحتاجه لرحلة لا تُنسى.',
                'cta_text' => 'تسوق الآن',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Banner 1 - Image
        |--------------------------------------------------------------------------
        */
        $banner1 = HeroBanner::create([
            'store_id'      => $store->id,
            'cat_url'       => '/shop',
            'position'      => 1,
            'visual_type'   => 'image',
            'image_path'    => 'hero/smartphones.jpg',
            'gradient_from' => null,
            'gradient_to'   => null,
            'is_active'     => true,
            'starts_at'     => $now,
            'ends_at'       => null,
        ]);

        $banner1->translations()->createMany([
            [
                'locale'    => 'en',
                'title'     => 'Latest Smartphones',
                'subtitle'  => 'Discover the newest arrivals',
                'cta_text'  => 'Shop Now',
            ],
            [
                'locale' => 'ar',
                'title' => 'أحدث الهواتف الذكية',
                'subtitle' => 'اكتشف أحدث الموديلات',
                'cta_text' => 'تسوق الآن',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Banner 2 - Gradient
        |--------------------------------------------------------------------------
        */
        $banner2 = HeroBanner::create([
            'store_id'      => $store->id,
            'cat_url'       => '/shop',
            'position'      => 2,
            'visual_type'   => 'gradient',
            'image_path'    => null,
            'gradient_from' => '#0F2027',
            'gradient_to'   => '#2C5364',
            'is_active'     => true,
            'starts_at'     => $now,
            'ends_at'       => $now->copy()->addMonth(),
        ]);

        $banner2->translations()->createMany([
            [
                'locale'    => 'en',
                'title'     => 'Powerful Laptops',
                'subtitle'  => 'Performance meets portability',
                'cta_text'  => 'Explore',
            ],
            [
                'locale' => 'ar',
                'title' => 'لاب توب بأداء قوي',
                'subtitle' => 'الأداء يلتقي مع سهولة التنقل',
                'cta_text' => 'اكتشف المزيد'
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Banner 3 - Image (Scheduled)
        |--------------------------------------------------------------------------
        */
        $banner3 = HeroBanner::create([
            'store_id'      => $store->id,
            'cat_url'       => '/shop',
            'position'      => 3,
            'visual_type'   => 'image',
            'image_path'    => 'hero/accessories.jpg',
            'gradient_from' => null,
            'gradient_to'   => null,
            'is_active'     => true,
            'starts_at'     => $now->copy()->addWeek(),
            'ends_at'       => $now->copy()->addMonths(2),
        ]);

        $banner3->translations()->createMany([
            [
                'locale'    => 'en',
                'title'     => 'Must-Have Accessories',
                'subtitle'  => 'Complete your setup',
                'cta_text'  => 'View Collection',
            ],
            [
                'locale'    => 'ar',
                'title'     => 'إكسسوارات لا غنى عنها',
                'subtitle'  => 'أكمل تجهيزاتك بأفضل المنتجات',
                'cta_text'  => 'تصفح المجموعة',
            ],
        ]);
    }
}
