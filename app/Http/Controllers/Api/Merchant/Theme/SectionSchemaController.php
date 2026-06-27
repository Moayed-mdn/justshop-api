<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant\Theme;

use App\Http\Controllers\Controller;
use App\Http\Resources\Theme\SectionSchemaResource;
use App\Models\SectionSchema;
use App\Traits\ApiResponserTrait;
use Illuminate\Http\JsonResponse;

class SectionSchemaController extends Controller
{
    use ApiResponserTrait;

    /**
     * List all active section schemas
     */
    public function index(): JsonResponse
    {
        $schemas = SectionSchema::active()->ordered()->get();

        return $this->success(SectionSchemaResource::collection($schemas));
    }
}
