<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchRequest;
use App\Services\SearchService;
use App\DTOs\Search\SearchDTO;


class SearchController extends Controller
{

    public function __construct(
        private SearchService $searchService,
    ) {}

    public function index(SearchRequest $request, int $store)
    {
        $dto = SearchDTO::fromRequest($request, $store);
        $results = $this->searchService->execute($dto);

        if ($results['type'] === 'all') {
            return $this->success([
                'type' => 'all',
                'products' => $results['results']['products'],
                'categories' => $results['results']['categories'],
            ], 'Search results retrieved successfully');
        }

        return $this->paginated(
            $results['results'],
            \App\Http\Resources\ProductCardResource::collection($results['results']),
            ['type' => $results['type']],
            'Search results retrieved successfully'
        );
    }
}