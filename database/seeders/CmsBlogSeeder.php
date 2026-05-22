<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CmsBlogSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::first();

        // 1. Create Categories
        $categories = [
            ['en' => 'Technology', 'ar' => 'تكنولوجيا'],
            ['en' => 'Lifestyle', 'ar' => 'نمط حياة'],
            ['en' => 'Business', 'ar' => 'أعمال'],
        ];

        foreach ($categories as $names) {
            BlogCategory::create([
                'name' => $names,
                'slug' => [
                    'en' => Str::slug($names['en']),
                    'ar' => Str::slug($names['ar']),
                ],
                'description' => [
                    'en' => "Articles about {$names['en']}",
                    'ar' => "مقالات عن {$names['ar']}",
                ],
            ]);
        }

        // 2. Create Tags
        $tags = [
            ['en' => 'Laravel', 'ar' => 'لارافيل'],
            ['en' => 'React', 'ar' => 'رياكت'],
            ['en' => 'Ecommerce', 'ar' => 'تجارة إلكترونية'],
        ];

        foreach ($tags as $names) {
            BlogTag::create([
                'name' => $names,
                'slug' => [
                    'en' => Str::slug($names['en']),
                    'ar' => Str::slug($names['ar']),
                ],
            ]);
        }

        // 3. Create Posts
        $category = BlogCategory::first();
        $tags = BlogTag::all();

        for ($i = 1; $i <= 5; $i++) {
            $post = BlogPost::create([
                'author_id' => $author->id,
                'blog_category_id' => $category->id,
                'title' => [
                    'en' => "Stabilizing CMS Architecture Part $i",
                    'ar' => "تثبيت معمارية نظام إدارة المحتوى الجزء $i",
                ],
                'slug' => [
                    'en' => "stabilizing-cms-architecture-part-$i",
                    'ar' => "تثبيت-معمارية-نظام-إدارة-المحتوى-الجزء-$i",
                ],
                'excerpt' => [
                    'en' => "Learn how we standardized localization and SEO in part $i.",
                    'ar' => "تعرف على كيفية توحيد التوطين وتحسين محركات البحث في الجزء $i.",
                ],
                'content' => [
                    'en' => "Full content for stabilizing CMS architecture part $i goes here. We used JSON maps for localization.",
                    'ar' => "المحتوى الكامل لتثبيت معمارية نظام إدارة المحتوى الجزء $i هنا. استخدمنا خرائط JSON للتوطين.",
                ],
                'seo' => [
                    'title' => [
                        'en' => "CMS Architecture Stabilization - Part $i",
                        'ar' => "تثبيت معمارية CMS - الجزء $i",
                    ],
                    'description' => [
                        'en' => "Standardizing CMS contracts for better frontend integration.",
                        'ar' => "توحيد عقود CMS لتكامل أفضل مع الواجهة الأمامية.",
                    ],
                    'robots' => [
                        'en' => 'index,follow',
                        'ar' => 'index,follow',
                    ],
                ],
                'is_published' => true,
                'published_at' => now()->subDays(5 - $i),
                'featured' => $i === 1,
                'reading_time' => 5,
                'created_by' => $author->id,
            ]);

            $post->tags()->attach($tags->pluck('id')->toArray());
        }
    }
}
