<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform\Mock;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mock Blog Controller for Frontend Development
 * This provides mock data for the platform dashboard blog management.
 */
class PlatformBlogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 25);
        $search = $request->query('search', '');
        $status = $request->query('status', '');

        // Generate 50 mock blog posts
        $allPosts = [];
        for ($i = 1; $i <= 50; $i++) {
            $allPosts[] = $this->generateMockPost($i);
        }

        // Filter by search
        if ($search) {
            $allPosts = array_filter($allPosts, function ($post) use ($search) {
                $searchLower = strtolower((string) $search);
                return str_contains(strtolower($post['translations']['en']['title']), $searchLower) ||
                       str_contains(strtolower($post['translations']['ar']['title'] ?? ''), $searchLower);
            });
        }

        // Filter by status
        if ($status) {
            $allPosts = array_filter($allPosts, fn ($post) => $post['publish_state'] === $status);
        }

        // Paginate
        $total = count($allPosts);
        $offset = ($page - 1) * $perPage;
        $posts = array_slice($allPosts, $offset, $perPage);

        return $this->paginated(
            collect($posts),
            $posts,
            [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
            ]
        );
    }

    public function show(int $id): JsonResponse
    {
        $post = $this->generateMockPost($id);
        return $this->success($post);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();
        $post = $this->generateMockPost(rand(51, 1000));
        $post['translations'] = $data['translations'] ?? [];
        $post['featured'] = $data['featured'] ?? false;
        $post['blog_category_id'] = $data['blog_category_id'] ?? null;
        $post['cover_image'] = $data['cover_image'] ?? null;
        $post['publish_state'] = $data['status'] ?? 'draft';

        return $this->success($post, 'blog.created', 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $post = $this->generateMockPost($id);
        $data = $request->all();

        if (isset($data['translations'])) {
            $post['translations'] = $data['translations'];
        }
        if (isset($data['featured'])) {
            $post['featured'] = $data['featured'];
        }
        if (isset($data['blog_category_id'])) {
            $post['blog_category_id'] = $data['blog_category_id'];
        }
        if (isset($data['cover_image'])) {
            $post['cover_image'] = $data['cover_image'];
        }

        return $this->success($post, 'blog.updated');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->success(null, 'blog.deleted');
    }

    public function publish(int $id): JsonResponse
    {
        $post = $this->generateMockPost($id);
        $post['publish_state'] = 'published';
        $post['is_published'] = true;
        $post['published_at'] = now()->toISOString();

        return $this->success($post, 'blog.published');
    }

    public function unpublish(int $id): JsonResponse
    {
        $post = $this->generateMockPost($id);
        $post['publish_state'] = 'draft';
        $post['is_published'] = false;
        $post['published_at'] = null;

        return $this->success($post, 'blog.unpublished');
    }

    public function schedule(int $id, Request $request): JsonResponse
    {
        $post = $this->generateMockPost($id);
        $post['publish_state'] = 'scheduled';
        $post['is_published'] = false;
        $post['published_at'] = $request->input('published_at');

        return $this->success($post, 'blog.scheduled');
    }

    public function categories(): JsonResponse
    {
        $categories = [
            [
                'id' => 1,
                'translations' => [
                    'en' => ['name' => 'Product Updates', 'slug' => 'product-updates'],
                    'ar' => ['name' => 'تحديثات المنتج', 'slug' => 'product-updates'],
                ],
                'created_at' => '2024-01-01T00:00:00.000000Z',
                'updated_at' => '2024-01-01T00:00:00.000000Z',
            ],
            [
                'id' => 2,
                'translations' => [
                    'en' => ['name' => 'Company News', 'slug' => 'company-news'],
                    'ar' => ['name' => 'أخبار الشركة', 'slug' => 'company-news'],
                ],
                'created_at' => '2024-01-01T00:00:00.000000Z',
                'updated_at' => '2024-01-01T00:00:00.000000Z',
            ],
            [
                'id' => 3,
                'translations' => [
                    'en' => ['name' => 'Tutorials', 'slug' => 'tutorials'],
                    'ar' => ['name' => 'دروس تعليمية', 'slug' => 'tutorials'],
                ],
                'created_at' => '2024-01-01T00:00:00.000000Z',
                'updated_at' => '2024-01-01T00:00:00.000000Z',
            ],
            [
                'id' => 4,
                'translations' => [
                    'en' => ['name' => 'Best Practices', 'slug' => 'best-practices'],
                    'ar' => ['name' => 'أفضل الممارسات', 'slug' => 'best-practices'],
                ],
                'created_at' => '2024-01-01T00:00:00.000000Z',
                'updated_at' => '2024-01-01T00:00:00.000000Z',
            ],
        ];

        return $this->success($categories);
    }

    public function tags(): JsonResponse
    {
        $tags = [
            [
                'id' => 1,
                'translations' => [
                    'en' => ['name' => 'E-commerce', 'slug' => 'ecommerce'],
                    'ar' => ['name' => 'التجارة الإلكترونية', 'slug' => 'ecommerce'],
                ],
                'created_at' => '2024-01-01T00:00:00.000000Z',
                'updated_at' => '2024-01-01T00:00:00.000000Z',
            ],
            [
                'id' => 2,
                'translations' => [
                    'en' => ['name' => 'Multi-Tenant', 'slug' => 'multi-tenant'],
                    'ar' => ['name' => 'متعدد المستأجرين', 'slug' => 'multi-tenant'],
                ],
                'created_at' => '2024-01-01T00:00:00.000000Z',
                'updated_at' => '2024-01-01T00:00:00.000000Z',
            ],
            [
                'id' => 3,
                'translations' => [
                    'en' => ['name' => 'SaaS', 'slug' => 'saas'],
                    'ar' => ['name' => 'SaaS', 'slug' => 'saas'],
                ],
                'created_at' => '2024-01-01T00:00:00.000000Z',
                'updated_at' => '2024-01-01T00:00:00.000000Z',
            ],
            [
                'id' => 4,
                'translations' => [
                    'en' => ['name' => 'Laravel', 'slug' => 'laravel'],
                    'ar' => ['name' => 'Laravel', 'slug' => 'laravel'],
                ],
                'created_at' => '2024-01-01T00:00:00.000000Z',
                'updated_at' => '2024-01-01T00:00:00.000000Z',
            ],
            [
                'id' => 5,
                'translations' => [
                    'en' => ['name' => 'Next.js', 'slug' => 'nextjs'],
                    'ar' => ['name' => 'Next.js', 'slug' => 'nextjs'],
                ],
                'created_at' => '2024-01-01T00:00:00.000000Z',
                'updated_at' => '2024-01-01T00:00:00.000000Z',
            ],
        ];

        return $this->success($tags);
    }

    private function generateMockPost(int $id): array
    {
        $statuses = ['draft', 'published', 'scheduled'];
        $status = $statuses[$id % 3];

        $titles = [
            'Introducing Our New Multi-Tenant E-commerce Platform',
            'How to Build Scalable SaaS Applications',
            'Best Practices for Laravel Development',
            'Getting Started with Next.js 15',
            'Understanding Multi-Tenancy in Modern Applications',
            'The Future of E-commerce: Trends to Watch',
            'Optimizing Performance in Laravel Applications',
            'Building Secure Authentication Systems',
            'A Complete Guide to REST API Development',
            'Mastering TypeScript for Frontend Development',
        ];

        $categoryIds = [1, 2, 3, 4];
        $tagIds = [[1, 2], [2, 3], [3, 4], [1, 4, 5], [2, 5]];

        $title = $titles[$id % count($titles)];
        $slug = strtolower(str_replace(' ', '-', $title));

        $daysAgo = rand(1, 90);
        $createdAt = now()->subDays($daysAgo)->toISOString();
        $publishedAt = $status === 'published' ? now()->subDays(rand(0, $daysAgo))->toISOString() : null;
        if ($status === 'scheduled') {
            $publishedAt = now()->addDays(rand(1, 30))->toISOString();
        }

        return [
            'id' => $id,
            'author_id' => rand(1, 5),
            'blog_category_id' => $categoryIds[$id % count($categoryIds)],
            'featured' => $id % 5 === 0,
            'is_published' => $status === 'published',
            'publish_state' => $status,
            'published_at' => $publishedAt,
            'cover_image' => $id % 2 === 0 ? 'https://via.placeholder.com/800x400' : null,
            'reading_time' => rand(3, 15),
            'category' => [
                'id' => $categoryIds[$id % count($categoryIds)],
                'translations' => [
                    'en' => ['name' => 'Category ' . ($id % count($categoryIds) + 1)],
                    'ar' => ['name' => 'فئة ' . ($id % count($categoryIds) + 1)],
                ],
            ],
            'tags' => array_map(fn ($tagId) => [
                'id' => $tagId,
                'translations' => [
                    'en' => ['name' => 'Tag ' . $tagId],
                    'ar' => ['name' => 'وسم ' . $tagId],
                ],
            ], $tagIds[$id % count($tagIds)]),
            'author' => [
                'id' => rand(1, 5),
                'name' => ['John Doe', 'Jane Smith', 'Ahmad Ali', 'Sara Johnson', 'Mohamed Hassan'][$id % 5],
                'email' => 'author' . ($id % 5 + 1) . '@example.com',
            ],
            'translations' => [
                'en' => [
                    'title' => $title,
                    'slug' => $slug,
                    'excerpt' => 'This is a brief excerpt of the blog post that summarizes the main topic.',
                    'content' => '<p>This is the full content of the blog post. It contains detailed information about the topic.</p><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
                    'meta_title' => $title,
                    'meta_description' => 'Learn about ' . strtolower($title),
                    'canonical_url' => 'https://example.com/blog/' . $slug,
                    'og_image' => null,
                    'robots' => 'index,follow',
                ],
                'ar' => [
                    'title' => 'عنوان المقالة باللغة العربية ' . $id,
                    'slug' => 'arabic-slug-' . $id,
                    'excerpt' => 'هذا ملخص قصير لمقالة المدونة',
                    'content' => '<p>هذا هو المحتوى الكامل لمقالة المدونة</p>',
                    'meta_title' => 'عنوان الميتا ' . $id,
                    'meta_description' => 'وصف الميتا لهذه المقالة',
                    'canonical_url' => 'https://example.com/ar/blog/arabic-slug-' . $id,
                    'og_image' => null,
                    'robots' => 'index,follow',
                ],
            ],
            'created_at' => $createdAt,
            'updated_at' => now()->subDays(rand(0, $daysAgo))->toISOString(),
            'created_by' => 1,
            'updated_by' => 1,
            'creator' => ['id' => 1, 'name' => 'Admin User'],
            'updater' => ['id' => 1, 'name' => 'Admin User'],
        ];
    }
}
