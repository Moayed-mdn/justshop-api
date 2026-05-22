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
            ->published()
            ->with($this->publicRelations($dto->locale));

        if ($dto->featured === true) {
            $query->featured();
        }

        if ($dto->category !== null) {
            $query->where("category->slug->$dto->locale", $dto->category);
        }

        if ($dto->tag !== null) {
            $query->whereHas('tags', function (Builder $builder) use ($dto): void {
                $builder->where("slug->$dto->locale", $dto->tag);
            });
        }

        if ($dto->search !== null) {
            $query->where(function (Builder $builder) use ($dto): void {
                $like = '%' . $dto->search . '%';
                $builder->where("title->$dto->locale", 'like', $like)
                    ->orWhere("excerpt->$dto->locale", 'like', $like)
                    ->orWhere("content->$dto->locale", 'like', $like);
            });
        }

        return $query->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($dto->perPage);
    }

    public function findPublishedBySlug(string $locale, string $slug): BlogPost
    {
        $post = BlogPost::query()
            ->published()
            ->where("slug->$locale", $slug)
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
            ->with($this->adminRelations());

        if ($dto->blogCategoryId !== null) {
            $query->where('blog_category_id', $dto->blogCategoryId);
        }

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

        if ($dto->featured !== null) {
            $query->where('featured', $dto->featured);
        }

        if ($dto->search !== null) {
            $query->where(function (Builder $builder) use ($dto): void {
                $like = '%' . $dto->search . '%';
                $builder->where('title', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('excerpt', 'like', $like);
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

    public function create(array $attributes, array $tagIds): BlogPost
    {
        $post = BlogPost::create($attributes);
        $post->tags()->sync($tagIds);

        return $this->refresh($post);
    }

    public function update(BlogPost $post, array $attributes, ?array $tagIds): BlogPost
    {
        $post->update($attributes);

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

    private function publicRelations(string $locale): array
    {
        return [
            'category' => fn ($builder) => $builder->select('id', 'name', 'slug'),
            'tags' => fn ($builder) => $builder->select('blog_tags.id', 'blog_tags.name', 'blog_tags.slug'),
            'author' => fn ($builder) => $builder->select(['id', 'name', 'avatar']),
        ];
    }

    private function adminRelations(): array
    {
        return [
            'category' => fn ($builder) => $builder->select('id', 'name', 'slug'),
            'tags' => fn ($builder) => $builder->select('blog_tags.id', 'blog_tags.name', 'blog_tags.slug'),
            'author' => fn ($builder) => $builder->select(['id', 'name', 'avatar']),
            'creator' => fn ($builder) => $builder->select(['id', 'name']),
            'updater' => fn ($builder) => $builder->select(['id', 'name']),
        ];
    }
}
