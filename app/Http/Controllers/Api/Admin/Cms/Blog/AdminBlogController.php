<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\Cms\Blog;

use App\Actions\Cms\Blog\Admin\CreateBlogPostAction;
use App\Actions\Cms\Blog\Admin\DeleteBlogPostAction;
use App\Actions\Cms\Blog\Admin\GetBlogPostAction;
use App\Actions\Cms\Blog\Admin\ListBlogPostsAction;
use App\Actions\Cms\Blog\Admin\PublishBlogPostAction;
use App\Actions\Cms\Blog\Admin\ScheduleBlogPostAction;
use App\Actions\Cms\Blog\Admin\UnpublishBlogPostAction;
use App\Actions\Cms\Blog\Admin\UpdateBlogPostAction;
use App\DTOs\Cms\Blog\Admin\CreateBlogPostDTO;
use App\DTOs\Cms\Blog\Admin\GetBlogPostDTO;
use App\DTOs\Cms\Blog\Admin\ListBlogPostsDTO;
use App\DTOs\Cms\Blog\Admin\PublishBlogPostDTO;
use App\DTOs\Cms\Blog\Admin\ScheduleBlogPostDTO;
use App\DTOs\Cms\Blog\Admin\UnpublishBlogPostDTO;
use App\DTOs\Cms\Blog\Admin\UpdateBlogPostDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\Blog\CreateBlogPostRequest;
use App\Http\Requests\Cms\Blog\DeleteBlogPostRequest;
use App\Http\Requests\Cms\Blog\ListBlogPostsRequest;
use App\Http\Requests\Cms\Blog\PublishBlogPostRequest;
use App\Http\Requests\Cms\Blog\ScheduleBlogPostRequest;
use App\Http\Requests\Cms\Blog\ShowBlogPostRequest;
use App\Http\Requests\Cms\Blog\UnpublishBlogPostRequest;
use App\Http\Requests\Cms\Blog\UpdateBlogPostRequest;
use App\Http\Resources\Cms\Blog\Admin\AdminBlogPostResource;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;

class AdminBlogController extends Controller
{
    public function index(
        ListBlogPostsRequest $request,
        ListBlogPostsAction $action,
    ): JsonResponse {
        $this->authorize('viewAny', BlogPost::class);

        $posts = $action->execute(ListBlogPostsDTO::fromRequest($request));

        return $this->paginated(
            $posts,
            AdminBlogPostResource::collection($posts->items())
        );
    }

    public function store(
        CreateBlogPostRequest $request,
        CreateBlogPostAction $action,
    ): JsonResponse {
        $this->authorize('create', BlogPost::class);

        $post = $action->execute(CreateBlogPostDTO::fromRequest($request));

        return $this->success(new AdminBlogPostResource($post), 'blog.created', 201);
    }

    public function show(
        ShowBlogPostRequest $request,
        BlogPost $blogPost,
        GetBlogPostAction $action,
    ): JsonResponse {
        $this->authorize('view', $blogPost);

        $post = $action->execute(
            GetBlogPostDTO::fromRequest($request, $blogPost->id),
            $blogPost
        );

        return $this->success(new AdminBlogPostResource($post));
    }

    public function update(
        UpdateBlogPostRequest $request,
        BlogPost $blogPost,
        UpdateBlogPostAction $action,
    ): JsonResponse {
        $this->authorize('update', $blogPost);

        $post = $action->execute(
            UpdateBlogPostDTO::fromRequest($request, $blogPost->id),
            $blogPost
        );

        return $this->success(new AdminBlogPostResource($post), 'blog.updated');
    }

    public function destroy(
        DeleteBlogPostRequest $request,
        BlogPost $blogPost,
        DeleteBlogPostAction $action,
    ): JsonResponse {
        $this->authorize('delete', $blogPost);

        $action->execute($blogPost->id, $blogPost);

        return $this->success(null, 'blog.deleted');
    }

    public function publish(
        PublishBlogPostRequest $request,
        BlogPost $blogPost,
        PublishBlogPostAction $action,
    ): JsonResponse {
        $this->authorize('publish', $blogPost);

        $post = $action->execute(
            PublishBlogPostDTO::fromRequest($request, $blogPost->id),
            $blogPost
        );

        return $this->success(new AdminBlogPostResource($post), 'blog.published');
    }

    public function unpublish(
        UnpublishBlogPostRequest $request,
        BlogPost $blogPost,
        UnpublishBlogPostAction $action,
    ): JsonResponse {
        $this->authorize('unpublish', $blogPost);

        $post = $action->execute(
            UnpublishBlogPostDTO::fromRequest($request, $blogPost->id),
            $blogPost
        );

        return $this->success(new AdminBlogPostResource($post), 'blog.unpublished');
    }

    public function schedule(
        ScheduleBlogPostRequest $request,
        BlogPost $blogPost,
        ScheduleBlogPostAction $action,
    ): JsonResponse {
        $this->authorize('schedule', $blogPost);

        $post = $action->execute(
            ScheduleBlogPostDTO::fromRequest($request, $blogPost->id),
            $blogPost
        );

        return $this->success(new AdminBlogPostResource($post), 'blog.scheduled');
    }
}
