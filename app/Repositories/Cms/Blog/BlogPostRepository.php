<?php

declare(strict_types=1);

namespace App\Repositories\Cms\Blog;

use App\DTOs\Cms\Blog\Admin\ListBlogPostsDTO;
use App\DTOs\Cms\Blog\ListPublicBlogPostsDTO;
use App\Enums\Cms\Blog\BlogPostPublishStateEnum;
use App\Exceptions\NotFoundException;
use App\Models\BlogPost;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class BlogPostRepository
{
    public function paginatePublic(ListPublicBlogPostsDTO $dto): LengthAwarePaginator
    {
        $query = BlogPost::query()
            ->select([
                'id',
                'author_id',
                'blog_category_id',
                'featured',
                'is_published',
                'published_at',
                'cover_image',
                'reading_time',
                'created_at',
            ])
            ->published()
            ->with($this->publicRelations($dto->locale));

        if ($dto->featured === true) {
            $query->featured();
        }

        if ($dto->category !== null) {
            $query->whereHas('category.translations', function (Builder $builder) use ($dto): void {
                $builder->where('locale', $dto->locale)
                    ->where('slug', $dto->category);
            });
        }

        if ($dto->tag !== null) {
            $query->whereHas('tags.translations', function (Builder $builder) use ($dto): void {
                $builder->where('locale', $dto->locale)
                    ->where('slug', $dto->tag);
            });
        }

        if ($dto->search !== null) {
            $query->whereHas('translations', function (Builder $builder) use ($dto): void {
                $builder->where('locale', $dto->locale)
                    ->where(function (Builder $translationQuery) use ($dto): void {
                        $like = '%' . $dto->search . '%';

                        $translationQuery->where('title', 'like', $like)
                            ->orWhere('excerpt', 'like', $like)
                            ->orWhere('content', 'like', $like);
                    });
            });
        }

        return $query->latest()->paginate($dto->perPage);
    }

    public function findPublishedBySlug(string $locale, string $slug): BlogPost
    {
        $post = BlogPost::query()
            ->select([
                'id',
                'author_id',
                'blog_category_id',
                'featured',
                'is_published',
                'published_at',
                'cover_image',
                'reading_time',
                'created_at',
                'updated_at',
            ])
            ->published()
            ->whereHas('translations', function (Builder $builder) use ($locale, $slug): void {
                $builder->where('locale', $locale)
                    ->where('slug', $slug);
            })
            ->with($this->publicRelations($locale))
            ->first();

        if ($post === null) {
            throw new NotFoundException(__('blog.post_not_found'));
        }

        return $post;
    }

    public function paginateAdmin(ListBlogPostsDTO $dto): LengthAwarePaginator
    {
        $query = BlogPost::query()
            ->select([
                'id',
                'author_id',
                'blog_category_id',
                'featured',
                'is_published',
                'published_at',
                'cover_image',
                'reading_time',
                'created_at',
                'updated_at',
                'created_by',
                'updated_by',
            ])
            ->with($this->adminRelations());

        if ($dto->publishState !== null && $dto->publishState !== 'all') {
            $query->where(function (Builder $builder) use ($dto): void {
                match ($dto->publishState) {
                    'draft' => $builder->where('is_published', false),
                    'published' => $builder->where('is_published', true)->where(fn($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now())),
                    'scheduled' => $builder->where('is_published', true)->where('published_at', '>', now()),
                    default => null,
                };
            });
        }

        if ($dto->authorId !== null) {
            $query->where('author_id', $dto->authorId);
        }

        if ($dto->blogCategoryId !== null) {
            $query->where('blog_category_id', $dto->blogCategoryId);
        }

        if ($dto->featured !== null) {
            $query->where('featured', $dto->featured);
        }

        if ($dto->search !== null) {
            $query->whereHas('translations', function (Builder $builder) use ($dto): void {
                if ($dto->locale !== null) {
                    $builder->where('locale', $dto->locale);
                }

                $builder->where(function (Builder $translationQuery) use ($dto): void {
                    $like = '%' . $dto->search . '%';

                    $translationQuery->where('title', 'like', $like)
                        ->orWhere('slug', 'like', $like)
                        ->orWhere('excerpt', 'like', $like);
                });
            });
        }

        return $query->latest('created_at')->paginate($dto->perPage);
    }

    public function findByIdOrFail(int $id): BlogPost
    {
        $post = BlogPost::query()
            ->with($this->adminRelations())
            ->find($id);

        if ($post === null) {
            throw new NotFoundException(__('blog.post_not_found'));
        }

        return $post;
    }

    public function create(array $attributes, array $translations, array $tagIds): BlogPost
    {
        $post = BlogPost::create($attributes);

        $this->syncTranslations($post, $translations);
        $post->tags()->sync($tagIds);

        return $this->refresh($post);
    }

    public function update(BlogPost $post, array $attributes, array $translations, ?array $tagIds): BlogPost
    {
        $post->update($attributes);

        $this->syncTranslations($post, $translations);

        if ($tagIds !== null) {
            $post->tags()->sync($tagIds);
        }

        return $this->refresh($post);
    }

    public function publish(BlogPost $post, CarbonInterface $publishedAt): BlogPost
    {
        $post->update([
            'is_published' => true,
            'published_at' => $publishedAt,
        ]);

        return $this->refresh($post);
    }

    public function unpublish(BlogPost $post): BlogPost
    {
        $post->update([
            'is_published' => false,
            'published_at' => null,
        ]);

        return $this->refresh($post);
    }

    public function schedule(BlogPost $post, CarbonInterface $publishedAt): BlogPost
    {
        $post->update([
            'is_published' => true,
            'published_at' => $publishedAt,
        ]);

        return $this->refresh($post);
    }

    public function delete(BlogPost $post): void
    {
        $post->delete();
    }

    public function refresh(BlogPost $post): BlogPost
    {
        return $post->fresh($this->adminRelations()) ?? $post->load($this->adminRelations());
    }

    private function syncTranslations(BlogPost $post, array $translations): void
    {
        foreach ($translations as $locale => $translation) {
            $post->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => $translation['title'],
                    'slug' => $translation['slug'],
                    'excerpt' => $translation['excerpt'],
                    'content' => $translation['content'],
                    'meta_title' => $translation['meta_title'],
                    'meta_description' => $translation['meta_description'],
                    'canonical_url' => $translation['canonical_url'],
                    'og_image' => $translation['og_image'],
                    'robots' => $translation['robots'],
                ],
            );
        }
    }

    private function publicRelations(string $locale): array
    {
        return [
            'translations' => fn ($builder) => $builder
                ->select([
                    'id',
                    'blog_post_id',
                    'locale',
                    'title',
                    'slug',
                    'excerpt',
                    'content',
                    'meta_title',
                    'meta_description',
                    'canonical_url',
                    'og_image',
                    'robots',
                ])
                ->where('locale', $locale),
            'category' => fn ($builder) => $builder->select('id'),
            'category.translations' => fn ($builder) => $builder
                ->select(['id', 'blog_category_id', 'locale', 'name', 'slug'])
                ->where('locale', $locale),
            'tags' => fn ($builder) => $builder->select('blog_tags.id'),
            'tags.translations' => fn ($builder) => $builder
                ->select(['id', 'blog_tag_id', 'locale', 'name', 'slug'])
                ->where('locale', $locale),
            'author' => fn ($builder) => $builder->select(['id', 'name', 'avatar']),
        ];
    }

    private function adminRelations(): array
    {
        return [
            'translations' => fn ($builder) => $builder->select([
                'id',
                'blog_post_id',
                'locale',
                'title',
                'slug',
                'excerpt',
                'content',
                'meta_title',
                'meta_description',
                'canonical_url',
                'og_image',
                'robots',
                'created_at',
                'updated_at',
            ]),
            'category' => fn ($builder) => $builder->select('id'),
            'category.translations' => fn ($builder) => $builder
                ->select(['id', 'blog_category_id', 'locale', 'name', 'slug']),
            'tags' => fn ($builder) => $builder->select('blog_tags.id'),
            'tags.translations' => fn ($builder) => $builder
                ->select(['id', 'blog_tag_id', 'locale', 'name', 'slug']),
            'author' => fn ($builder) => $builder->select(['id', 'name', 'avatar']),
            'creator' => fn ($builder) => $builder->select(['id', 'name']),
            'updater' => fn ($builder) => $builder->select(['id', 'name']),
        ];
    }
}
