<?php

namespace Tests\Feature\Admin;

use App\DTOs\Admin\Product\CreateProductDTO;
use App\DTOs\Admin\Product\UpdateProductDTO;
use App\Http\Requests\Admin\Product\CreateProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use Tests\TestCase;

class AdminProductDtoTest extends TestCase
{
    public function test_update_dto_keeps_omitted_optional_fields_as_null(): void
    {
        /** @var UpdateProductRequest $request */
        $request = UpdateProductRequest::create('/api/v1/admin/stores/1/products/39', 'PATCH', [
            'translations' => [
                [
                    'locale' => 'ar',
                    'name' => 'اسم',
                    'slug' => 'slug',
                ],
            ],
        ]);

        $dto = UpdateProductDTO::fromRequest($request, 1, 39);

        $this->assertNull($dto->categoryId);
        $this->assertNull($dto->brandId);
        $this->assertNull($dto->isActive);
        $this->assertFalse($dto->categoryIdProvided);
        $this->assertFalse($dto->brandIdProvided);
    }

    public function test_update_dto_tracks_explicit_null_category_and_brand(): void
    {
        /** @var UpdateProductRequest $request */
        $request = UpdateProductRequest::create('/api/v1/merchant/stores/1/products/39', 'PATCH', [
            'category_id' => null,
            'brand_id'    => null,
        ]);

        $dto = UpdateProductDTO::fromRequest($request, 1, 39);

        $this->assertNull($dto->categoryId);
        $this->assertNull($dto->brandId);
        $this->assertTrue($dto->categoryIdProvided);
        $this->assertTrue($dto->brandIdProvided);
    }

    public function test_create_dto_keeps_nullable_brand_id_as_null_when_omitted(): void
    {
        /** @var CreateProductRequest $request */
        $request = CreateProductRequest::create('/api/v1/admin/stores/1/products', 'POST', [
            'category_id' => 4,
            'translations' => [
                [
                    'locale' => 'ar',
                    'name' => 'اسم',
                    'slug' => 'slug',
                ],
            ],
            'variants' => [
                [
                    'sku' => 'SKU-1',
                    'price' => 10,
                    'quantity' => 2,
                ],
            ],
        ]);

        $dto = CreateProductDTO::fromRequest($request, 1);

        $this->assertSame(4, $dto->categoryId);
        $this->assertNull($dto->brandId);
    }
}
