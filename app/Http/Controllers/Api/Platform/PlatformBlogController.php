<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Platform Blog Controller
 * 
 * Manages blog posts for the platform (NOT store-specific).
 */
class PlatformBlogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Log::info('PlatformBlogController index', [
            'has_permission' => auth()->user()?->can(\App\Enums\PermissionEnum::CMS_BLOG_VIEW),
            'user_id' => auth()->user()?->id
        ]);

        if (!auth()->user()?->can(\App\Enums\PermissionEnum::CMS_BLOG_VIEW)) {
            abort(403, 'This action is unauthorized.');
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));
        $search = $request->query('search', '');
        $status = $request->query('status', '');

        $query = BlogPost::query()
            ->with(['category', 'tags', 'author', 'creator', 'updater'])
            ->orderByDesc('created_at');

        // Filter by search (search in English title)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("JSON_EXTRACT(title, '$.en') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_EXTRACT(title, '$.ar') LIKE ?", ["%{$search}%"]);
            });
        }

        // Filter by status
        if ($status) {
            if ($status === 'published') {
                $query->where('is_published', true)
                    ->where(function ($q) {
                        $q->whereNull('published_at')
                            ->orWhere('published_at', '<=', now());
                    });
            } elseif ($status === 'draft') {
                $query->where('is_published', false);
            } elseif ($status === 'scheduled') {
                $query->where('is_published', true)
                    ->where('published_at', '>', now());
            }
        }

        $posts = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $posts->map(function (BlogPost $post) {
            return $this->formatBlogPost($post);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'from' => $posts->firstItem(),
                'to' => $posts->lastItem(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        if (!auth()->user()?->can(\App\Enums\PermissionEnum::CMS_BLOG_VIEW)) {
            abort(403, 'This action is unauthorized.');
        }

        $post = BlogPost::with(['category', 'tags', 'author', 'creator', 'updater'])
            ->findOrFail($id);

        return $this->success($this->formatBlogPost($post));
    }

    public function store(Request $request): JsonResponse
    {
        if (!auth()->user()?->can(\App\Enums\PermissionEnum::CMS_BLOG_CREATE)) {
            abort(403, 'This action is unauthorized.');
        }

        $validated = $request->validate([
            'translations' => 'required|array',
            'translations.en' => 'required|array',
            'translations.en.title' => 'required|string',
            'translations.en.slug' => 'required|string',
            'translations.en.excerpt' => 'nullable|string',
            'translations.en.content' => 'nullable|string',
            'blog_category_id' => 'nullable|exists:blog_categories,id',
            'featured' => 'boolean',
            'cover_image' => 'nullable|string',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:blog_tags,id',
        ]);

        $post = BlogPost::create([
            'title' => $this->extractTranslations($validated['translations'], 'title'),
            'slug' => $this->extractTranslations($validated['translations'], 'slug'),
            'excerpt' => $this->extractTranslations($validated['translations'], 'excerpt'),
            'content' => $this->extractTranslations($validated['translations'], 'content'),
            'seo' => $this->extractSeo($validated['translations']),
            'blog_category_id' => $validated['blog_category_id'] ?? null,
            'featured' => $validated['featured'] ?? false,
            'cover_image' => $validated['cover_image'] ?? null,
            'is_published' => false,
            'author_id' => auth()->id(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        if (!empty($validated['tag_ids'])) {
            $post->tags()->sync($validated['tag_ids']);
        }

        $post->load(['category', 'tags', 'author', 'creator', 'updater']);

        return $this->success($this->formatBlogPost($post), 'blog.created', 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        if (!auth()->user()?->can(\App\Enums\PermissionEnum::CMS_BLOG_UPDATE)) {
            abort(403, 'This action is unauthorized.');
        }

        $post = BlogPost::findOrFail($id);

        $validated = $request->validate([
            'translations' => 'sometimes|array',
            'blog_category_id' => 'nullable|exists:blog_categories,id',
            'featured' => 'boolean',
            'cover_image' => 'nullable|string',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:blog_tags,id',
        ]);

        $updateData = [];

        if (isset($validated['translations'])) {
            $updateData['title'] = $this->extractTranslations($validated['translations'], 'title');
            $updateData['slug'] = $this->extractTranslations($validated['translations'], 'slug');
            $updateData['excerpt'] = $this->extractTranslations($validated['translations'], 'excerpt');
            $updateData['content'] = $this->extractTranslations($validated['translations'], 'content');
            $updateData['seo'] = $this->extractSeo($validated['translations']);
        }

        if (isset($validated['blog_category_id'])) {
            $updateData['blog_category_id'] = $validated['blog_category_id'];
        }

        if (isset($validated['featured'])) {
            $updateData['featured'] = $validated['featured'];
        }

        if (isset($validated['cover_image'])) {
            $updateData['cover_image'] = $validated['cover_image'];
        }

        $updateData['updated_by'] = auth()->id();

        $post->update($updateData);

        if (isset($validated['tag_ids'])) {
            $post->tags()->sync($validated['tag_ids']);
        }

        $post->load(['category', 'tags', 'author', 'creator', 'updater']);

        return $this->success($this->formatBlogPost($post), 'blog.updated');
    }

    public function destroy(int $id): JsonResponse
    {
        if (!auth()->user()?->can(\App\Enums\PermissionEnum::CMS_BLOG_DELETE)) {
            abort(403, 'This action is unauthorized.');
        }

        $post = BlogPost::findOrFail($id);
        $post->delete();

        return $this->success(null, 'blog.deleted');
    }

    public function publish(int $id): JsonResponse
    {
        if (!auth()->user()?->can(\App\Enums\PermissionEnum::CMS_BLOG_PUBLISH)) {
            abort(403, 'This action is unauthorized.');
        }

        $post = BlogPost::findOrFail($id);
        $post->update([
            'is_published' => true,
            'published_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        $post->load(['category', 'tags', 'author', 'creator', 'updater']);

        return $this->success($this->formatBlogPost($post), 'blog.published');
    }

    public function unpublish(int $id): JsonResponse
    {
        if (!auth()->user()?->can(\App\Enums\PermissionEnum::CMS_BLOG_PUBLISH)) {
            abort(403, 'This action is unauthorized.');
        }

        $post = BlogPost::findOrFail($id);
        $post->update([
            'is_published' => false,
            'published_at' => null,
            'updated_by' => auth()->id(),
        ]);

        $post->load(['category', 'tags', 'author', 'creator', 'updater']);

        return $this->success($this->formatBlogPost($post), 'blog.unpublished');
    }

    public function schedule(int $id, Request $request): JsonResponse
    {
        if (!auth()->user()?->can(\App\Enums\PermissionEnum::CMS_BLOG_PUBLISH)) {
            abort(403, 'This action is unauthorized.');
        }

        $validated = $request->validate([
            'published_at' => 'required|date|after:now',
        ]);

        $post = BlogPost::findOrFail($id);
        $post->update([
            'is_published' => true,
            'published_at' => $validated['published_at'],
            'updated_by' => auth()->id(),
        ]);

        $post->load(['category', 'tags', 'author', 'creator', 'updater']);

        return $this->success($this->formatBlogPost($post), 'blog.scheduled');
    }

    public function categories(): JsonResponse
    {
        $categories = BlogCategory::all()->map(function (BlogCategory $category) {
            return [
                'id' => $category->id,
                'translations' => [
                    'en' => [
                        'name' => $category->name['en'] ?? '',
                        'slug' => $category->slug['en'] ?? '',
                    ],
                    'ar' => [
                        'name' => $category->name['ar'] ?? '',
                        'slug' => $category->slug['ar'] ?? '',
                    ],
                ],
                'created_at' => $category->created_at->toISOString(),
                'updated_at' => $category->updated_at->toISOString(),
            ];
        });

        return $this->success($categories);
    }

    public function tags(): JsonResponse
    {
        $tags = BlogTag::all()->map(function (BlogTag $tag) {
            return [
                'id' => $tag->id,
                'translations' => [
                    'en' => [
                        'name' => $tag->name['en'] ?? '',
                        'slug' => $tag->slug['en'] ?? '',
                    ],
                    'ar' => [
                        'name' => $tag->name['ar'] ?? '',
                        'slug' => $tag->slug['ar'] ?? '',
                    ],
                ],
                'created_at' => $tag->created_at->toISOString(),
                'updated_at' => $tag->updated_at->toISOString(),
            ];
        });

        return $this->success($tags);
    }

    private function formatBlogPost(BlogPost $post): array
    {
        $publishState = 'draft';
        if ($post->is_published) {
            $publishState = $post->published_at && $post->published_at->isFuture()
                ? 'scheduled'
                : 'published';
        }

        return [
            'id' => $post->id,
            'author_id' => $post->author_id,
            'blog_category_id' => $post->blog_category_id,
            'featured' => $post->featured,
            'is_published' => $post->is_published,
            'publish_state' => $publishState,
            'published_at' => $post->published_at?->toISOString(),
            'cover_image' => $post->cover_image,
            'reading_time' => $post->reading_time,
            'category' => $post->category ? [
                'id' => $post->category->id,
                'translations' => [
                    'en' => ['name' => $post->category->name['en'] ?? ''],
                    'ar' => ['name' => $post->category->name['ar'] ?? ''],
                ],
            ] : null,
            'tags' => $post->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'translations' => [
                    'en' => ['name' => $tag->name['en'] ?? ''],
                    'ar' => ['name' => $tag->name['ar'] ?? ''],
                ],
            ]),
            'author' => $post->author ? [
                'id' => $post->author->id,
                'name' => $post->author->name,
                'email' => $post->author->email,
            ] : null,
            'translations' => [
                'en' => [
                    'title' => $post->title['en'] ?? '',
                    'slug' => $post->slug['en'] ?? '',
                    'excerpt' => $post->excerpt['en'] ?? '',
                    'content' => $post->content['en'] ?? '',
                    'meta_title' => $post->seo['en']['meta_title'] ?? '',
                    'meta_description' => $post->seo['en']['meta_description'] ?? '',
                    'canonical_url' => $post->seo['en']['canonical_url'] ?? null,
                    'og_image' => $post->seo['en']['og_image'] ?? null,
                    'robots' => $post->seo['en']['robots'] ?? 'index,follow',
                ],
                'ar' => [
                    'title' => $post->title['ar'] ?? '',
                    'slug' => $post->slug['ar'] ?? '',
                    'excerpt' => $post->excerpt['ar'] ?? '',
                    'content' => $post->content['ar'] ?? '',
                    'meta_title' => $post->seo['ar']['meta_title'] ?? '',
                    'meta_description' => $post->seo['ar']['meta_description'] ?? '',
                    'canonical_url' => $post->seo['ar']['canonical_url'] ?? null,
                    'og_image' => $post->seo['ar']['og_image'] ?? null,
                    'robots' => $post->seo['ar']['robots'] ?? 'index,follow',
                ],
            ],
            'created_at' => $post->created_at->toISOString(),
            'updated_at' => $post->updated_at->toISOString(),
            'created_by' => $post->created_by,
            'updated_by' => $post->updated_by,
            'creator' => $post->creator ? ['id' => $post->creator->id, 'name' => $post->creator->name] : null,
            'updater' => $post->updater ? ['id' => $post->updater->id, 'name' => $post->updater->name] : null,
        ];
    }

    private function extractTranslations(array $translations, string $field): array
    {
        $result = [];
        foreach ($translations as $locale => $data) {
            if (isset($data[$field])) {
                $result[$locale] = $data[$field];
            }
        }
        return $result;
    }

    private function extractSeo(array $translations): array
    {
        $result = [];
        foreach ($translations as $locale => $data) {
            $result[$locale] = [
                'meta_title' => $data['meta_title'] ?? '',
                'meta_description' => $data['meta_description'] ?? '',
                'canonical_url' => $data['canonical_url'] ?? null,
                'og_image' => $data['og_image'] ?? null,
                'robots' => $data['robots'] ?? 'index,follow',
            ];
        }
        return $result;
    }
}

