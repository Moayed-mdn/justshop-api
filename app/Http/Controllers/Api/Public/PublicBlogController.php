<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Actions\Cms\Blog\GetPublicBlogPostAction;
use App\Actions\Cms\Blog\ListPublicBlogPostsAction;
use App\DTOs\Cms\Blog\GetPublicBlogPostDTO;
use App\DTOs\Cms\Blog\ListPublicBlogPostsDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\Blog\GetPublicBlogPostRequest;
use App\Http\Requests\Cms\Blog\ListPublicBlogPostsRequest;
use App\Http\Resources\Cms\Blog\PublicBlogPostCollection;
use App\Http\Resources\Cms\Blog\PublicBlogPostResource;
use Illuminate\Http\JsonResponse;

class PublicBlogController extends Controller
{
    public function __construct(
        private ListPublicBlogPostsAction $listPublicBlogPostsAction,
        private GetPublicBlogPostAction $getPublicBlogPostAction,
    ) {}

    public function index(ListPublicBlogPostsRequest $request): JsonResponse
    {
        $posts = $this->listPublicBlogPostsAction->execute(
            ListPublicBlogPostsDTO::fromRequest($request)
        );

        return $this->success(new PublicBlogPostCollection($posts));
    }

    public function show(GetPublicBlogPostRequest $request, string $slug): JsonResponse
    {
        $post = $this->getPublicBlogPostAction->execute(
            GetPublicBlogPostDTO::fromRequest($request, $slug)
        );

        return $this->success(new PublicBlogPostResource($post));
    }
}
