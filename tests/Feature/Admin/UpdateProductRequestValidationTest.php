<?php

namespace Tests\Feature\Admin;

use App\Http\Requests\Admin\Product\UpdateProductRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateProductRequestValidationTest extends TestCase
{
    public function test_allows_multiple_variants_with_null_skus(): void
    {
        $request = UpdateProductRequest::create('/api/v1/merchant/stores/1/products/1', 'PATCH', [
            'sync_variants' => true,
            'variants' => [
                ['sku' => null, 'price' => 10, 'quantity' => 1],
                ['sku' => null, 'price' => 12, 'quantity' => 2],
            ],
        ]);

        $validator = Validator::make(
            $request->all(),
            $request->rules(),
        );
        $request->withValidator($validator);
        $validator->validate();

        $this->assertTrue($validator->passes());
    }

    public function test_rejects_duplicate_non_empty_skus(): void
    {
        $request = UpdateProductRequest::create('/api/v1/merchant/stores/1/products/1', 'PATCH', [
            'sync_variants' => true,
            'variants' => [
                ['sku' => 'DUPE-1', 'price' => 10, 'quantity' => 1],
                ['sku' => 'dupe-1', 'price' => 12, 'quantity' => 2],
            ],
        ]);

        $validator = Validator::make(
            $request->all(),
            $request->rules(),
        );
        $request->withValidator($validator);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('variants'));
    }

    public function test_rejects_unknown_variant_option_names(): void
    {
        $request = UpdateProductRequest::create('/api/v1/merchant/stores/1/products/1', 'PATCH', [
            'sync_variants' => true,
            'options' => [
                ['name' => 'Color', 'position' => 1, 'values' => ['Red']],
            ],
            'variants' => [
                [
                    'price'    => 10,
                    'quantity' => 1,
                    'options'  => ['Size' => 'M'],
                ],
            ],
        ]);

        $validator = Validator::make(
            $request->all(),
            $request->rules(),
        );
        $request->withValidator($validator);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('variants.0.options.Size'));
    }

    public function test_allows_expiry_date_without_manufacture_date(): void
    {
        $request = UpdateProductRequest::create('/api/v1/merchant/stores/1/products/1', 'PATCH', [
            'sync_variants' => true,
            'variants' => [
                [
                    'price'       => 10,
                    'quantity'    => 1,
                    'expiry_date' => '2026-12-31',
                ],
            ],
        ]);

        $validator = Validator::make(
            $request->all(),
            $request->rules(),
        );
        $request->withValidator($validator);
        $validator->validate();

        $this->assertTrue($validator->passes());
    }

    public function test_rejects_expiry_date_before_manufacture_date(): void
    {
        $request = UpdateProductRequest::create('/api/v1/merchant/stores/1/products/1', 'PATCH', [
            'sync_variants' => true,
            'variants' => [
                [
                    'price'            => 10,
                    'quantity'         => 1,
                    'manufacture_date' => '2026-06-01',
                    'expiry_date'      => '2026-01-01',
                ],
            ],
        ]);

        $validator = Validator::make(
            $request->all(),
            $request->rules(),
        );
        $request->withValidator($validator);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('variants.0.expiry_date'));
    }
}
