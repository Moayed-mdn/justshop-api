<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Actions\Cms\Blog\ArchiveBlogPostAction;
use App\Actions\Cms\Blog\CreateBlogPostAction;
use App\Actions\Cms\Blog\DeleteBlogPostAction;
use App\Actions\Cms\Blog\GetBlogCategoriesAction;
use App\Actions\Cms\Blog\GetBlogPostAction;
use App\Actions\Cms\Blog\GetBlogPostsAction;
use App\Actions\Cms\Blog\GetBlogTagsAction;
use App\Actions\Cms\Blog\PublishBlogPostAction;
use App\Actions\Cms\Blog\ScheduleBlogPostAction;
use App\Actions\Cms\Blog\UnpublishBlogPostAction;
use App\Actions\Cms\Blog\UpdateBlogPostAction;
use App\DTOs\Cms\Blog\CreateBlogPostDTO;
use App\DTOs\Cms\Blog\GetBlogPostDTO;
use App\DTOs\Cms\Blog\GetBlogPostsDTO;
use App\DTOs\Cms\Blog\PublishBlogPostDTO;
use App\DTOs\Cms\Blog\ScheduleBlogPostDTO;
use App\DTOs\Cms\Blog\UpdateBlogPostDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\Cms\Blog\CreateBlogPostRequest;
use App\Http\Requests\Platform\Cms\Blog\GetBlogPostsRequest;
use App\Http\Requests\Platform\Cms\Blog\ScheduleBlogPostRequest;
use App\Http\Requests\Platform\Cms\Blog\UpdateBlogPostRequest;
use App\Http\Resources\Cms\Blog\BlogCategoryResource;
use App\Http\Resources\Cms\Blog\BlogPostResource;
use App\Http\Resources\Cms\Blog\BlogTagResource;
use App\Policies\Cms\BlogPostPolicy;
use Illuminate\Http\JsonResponse;

/**
 * Platform Blog Controller
 * 
 * Manages blog posts for the platform (NOT store-specific).
 * Follows architecture rules:
 * - Thin controller (entry point only)
 * - Uses FormRequests for validation
 * - Uses DTOs for data transfer
 * - Delegates to Actions for business logic
 * - Uses Policies for authorization
 * - Uses API Resources for response transformation
 */
class PlatformBlogController extends Controller
{
    public function __construct(
        private readonly GetBlogPostsAction $getBlogPostsAction,
        private readonly GetBlogPostAction $getBlogPostAction,
        private readonly CreateBlogPostAction $createBlogPostAction,
        private readonly UpdateBlogPostAction $updateBlogPostAction,
        private readonly DeleteBlogPostAction $deleteBlogPostAction,
        private readonly PublishBlogPostAction $publishBlogPostAction,
        private readonly UnpublishBlogPostAction $unpublishBlogPostAction,
        private readonly ScheduleBlogPostAction $scheduleBlogPostAction,
        private readonly ArchiveBlogPostAction $archiveBlogPostAction,
        private readonly GetBlogCategoriesAction $getBlogCategoriesAction,
        private readonly GetBlogTagsAction $getBlogTagsAction,
    ) {}

    public function index(GetBlogPostsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', BlogPostPolicy::class);

        $posts = $this->getBlogPostsAction->execute(
            GetBlogPostsDTO::fromRequest($request)
        );

        return $this->paginated($posts, BlogPostResource::collection($posts));
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize('viewAny', BlogPostPolicy::class);

        $post = $this->getBlogPostAction->execute(
            new GetBlogPostDTO(postId: $id)
        );

        return $this->success(new BlogPostResource($post));
    }

    public function store(CreateBlogPostRequest $request): JsonResponse
    {
        $this->authorize('create', BlogPostPolicy::class);

        $post = $this->createBlogPostAction->execute(
            CreateBlogPostDTO::fromRequest($request)
        );

        return $this->success(new BlogPostResource($post), __('cms.blog.created'), 201);
    }

    public function update(int $id, UpdateBlogPostRequest $request): JsonResponse
    {
        $post = $this->getBlogPostAction->execute(
            new GetBlogPostDTO(postId: $id)
        );

        $this->authorize('update', [BlogPostPolicy::class, $post]);

        $updatedPost = $this->updateBlogPostAction->execute(
            UpdateBlogPostDTO::fromRequest($request, $id)
        );

        return $this->success(new BlogPostResource($updatedPost), __('cms.blog.updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $post = $this->getBlogPostAction->execute(
            new GetBlogPostDTO(postId: $id)
        );

        $this->authorize('delete', [BlogPostPolicy::class, $post]);

        $this->deleteBlogPostAction->execute($id);

        return $this->success(null, __('cms.blog.deleted'));
    }

    public function publish(int $id): JsonResponse
    {
        $post = $this->getBlogPostAction->execute(
            new GetBlogPostDTO(postId: $id)
        );

        $this->authorize('publish', [BlogPostPolicy::class, $post]);

        $publishedPost = $this->publishBlogPostAction->execute(
            new PublishBlogPostDTO(postId: $id)
        );

        return $this->success(new BlogPostResource($publishedPost), __('cms.blog.published'));
    }

    public function unpublish(int $id): JsonResponse
    {
        $post = $this->getBlogPostAction->execute(
            new GetBlogPostDTO(postId: $id)
        );

        $this->authorize('publish', [BlogPostPolicy::class, $post]);

        $unpublishedPost = $this->unpublishBlogPostAction->execute($id);

        return $this->success(new BlogPostResource($unpublishedPost), __('cms.blog.unpublished'));
    }

    public function schedule(int $id, ScheduleBlogPostRequest $request): JsonResponse
    {
        $post = $this->getBlogPostAction->execute(
            new GetBlogPostDTO(postId: $id)
        );

        $this->authorize('publish', [BlogPostPolicy::class, $post]);

        $scheduledPost = $this->scheduleBlogPostAction->execute(
            ScheduleBlogPostDTO::fromRequest($request, $id)
        );

        return $this->success(new BlogPostResource($scheduledPost), __('cms.blog.scheduled'));
    }

    public function categories(): JsonResponse
    {
        $this->authorize('viewAny', BlogPostPolicy::class);

        $categories = $this->getBlogCategoriesAction->execute();

        return $this->success(BlogCategoryResource::collection($categories));
    }

    public function tags(): JsonResponse
    {
        $this->authorize('viewAny', BlogPostPolicy::class);

        $tags = $this->getBlogTagsAction->execute();

        return $this->success(BlogTagResource::collection($tags));
    }
}
