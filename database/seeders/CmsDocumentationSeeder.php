<?php

namespace Database\Seeders;

use App\Models\Cms\CmsDocument;
use App\Models\Cms\CmsDocumentSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CmsDocumentationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Sections
        $sections = [
            ['en' => 'Getting Started', 'ar' => 'البدء'],
            ['en' => 'API Reference', 'ar' => 'مرجع API'],
            ['en' => 'Integrations', 'ar' => 'التكاملات'],
        ];

        foreach ($sections as $index => $names) {
            $section = CmsDocumentSection::create([
                'title' => $names,
                'slug' => [
                    'en' => Str::slug($names['en']),
                    'ar' => Str::slug($names['ar']),
                ],
                'description' => [
                    'en' => "Learn about {$names['en']}",
                    'ar' => "تعرف على {$names['ar']}",
                ],
                'sort_order' => $index,
                'is_published' => true,
                'published_at' => now(),
            ]);

            // 2. Create Documents for each section
            for ($i = 1; $i <= 3; $i++) {
                CmsDocument::create([
                    'section_id' => $section->id,
                    'title' => [
                        'en' => "{$names['en']} Guide $i",
                        'ar' => "دليل {$names['ar']} $i",
                    ],
                    'slug' => [
                        'en' => Str::slug("{$names['en']} Guide $i"),
                        'ar' => Str::slug("دليل {$names['ar']} $i"),
                    ],
                    'excerpt' => [
                        'en' => "Summary of {$names['en']} Guide $i",
                        'ar' => "ملخص دليل {$names['ar']} $i",
                    ],
                    'content' => [
                        'en' => "Full documentation content for {$names['en']} Guide $i. This is platform-level content.",
                        'ar' => "محتوى التوثيق الكامل لـ دليل {$names['ar']} $i. هذا محتوى على مستوى المنصة.",
                    ],
                    'seo' => [
                        'title' => [
                            'en' => "{$names['en']} Guide $i | Documentation",
                            'ar' => "دليل {$names['ar']} $i | التوثيق",
                        ],
                        'description' => [
                            'en' => "Comprehensive guide for {$names['en']} in our platform.",
                            'ar' => "دليل شامل لـ {$names['ar']} في منصتنا.",
                        ],
                    ],
                    'sort_order' => $i,
                    'is_published' => true,
                    'published_at' => now(),
                ]);
            }
        }
    }
}
