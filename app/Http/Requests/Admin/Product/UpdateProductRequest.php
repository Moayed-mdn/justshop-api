<?php

namespace App\Http\Requests\Admin\Product;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            /*
            |------------------------------------------------------------------
            | Product
            |------------------------------------------------------------------
            */

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'brand_id' => [
                'nullable',
                'integer',
                'exists:brands,id',
            ],

            'is_active' => [
                'sometimes',
                'nullable',
                'boolean',
            ],

            'is_featured' => [
                'sometimes',
                'nullable',
                'boolean',
            ],

            'default_variant_id' => [
                'sometimes',
                'nullable',
                'integer',
            ],

            'sync_variants' => [
                'sometimes',
                'nullable',
                'boolean',
            ],

            /*
            |------------------------------------------------------------------
            | Translations
            |------------------------------------------------------------------
            */

            'translations' => [
                'sometimes',
                'nullable',
                'array',
                'min:1',
            ],

            'translations.*.locale' => [
                'required_with:translations',
                'string',
                'size:2',
                Rule::in(
                    config(
                        'content.editable_locales',
                        config('app.supported_locales', [])
                    )
                ),
            ],

            'translations.*.name' => [
                'required_with:translations',
                'string',
                'max:255',
            ],

            'translations.*.slug' => [
                'required_with:translations',
                'string',
                'max:255',
            ],

            'translations.*.description' => [
                'nullable',
                'string',
            ],

            'translations.*.seo_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'translations.*.seo_description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            /*
            |------------------------------------------------------------------
            | Canonical Product Options
            |------------------------------------------------------------------
            */

            'options' => [
                'sometimes',
                'nullable',
                'array',
                'max:3',
            ],

            'options.*.name' => [
                'required',
                'string',
                'max:100',
            ],

            'options.*.position' => [
                'required',
                'integer',
                'min:1',
            ],

            'options.*.values' => [
                'required',
                'array',
                'min:1',
            ],

            'options.*.values.*' => [
                'required',
                'string',
                'max:100',
            ],

            /*
            |------------------------------------------------------------------
            | Variants
            |------------------------------------------------------------------
            */

            'variants' => [
                'sometimes',
                'nullable',
                'array',
            ],

            'variants.*.id' => [
                'sometimes',
                'nullable',
                'integer',
            ],

            /*
            |------------------------------------------------------------------
            | Variant Core
            |------------------------------------------------------------------
            */

            'variants.*.sku' => [
                'nullable',
                'string',
                'max:100',
            ],

            'variants.*.barcode' => [
                'nullable',
                'string',
                'max:100',
            ],

            'variants.*.price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'variants.*.compare_at_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'variants.*.cost_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'variants.*.quantity' => [
                'required',
                'integer',
                'min:0',
            ],

            'variants.*.low_stock_threshold' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'variants.*.track_inventory' => [
                'sometimes',
                'nullable',
                'boolean',
            ],

            'variants.*.is_active' => [
                'sometimes',
                'nullable',
                'boolean',
            ],

            /*
            |------------------------------------------------------------------
            | Physical / Shipping
            |------------------------------------------------------------------
            */

            'variants.*.weight' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'variants.*.weight_unit' => [
                'nullable',
                'string',
                Rule::in(['g', 'kg', 'lb']),
            ],

            /*
            |------------------------------------------------------------------
            | Batch / Expiry
            |------------------------------------------------------------------
            */

            'variants.*.manufacture_date' => [
                'nullable',
                'date',
            ],

            'variants.*.expiry_date' => [
                'nullable',
                'date',
            ],

            'variants.*.batch_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            /*
            |------------------------------------------------------------------
            | Variant Option Mapping
            |------------------------------------------------------------------
            */

            'variants.*.options' => [
                'sometimes',
                'nullable',
                'array',
            ],

            'variants.*.options.*' => [
                'sometimes',
                'string',
                'max:100',
            ],

            /*
            |------------------------------------------------------------------
            | Variant Media
            |------------------------------------------------------------------
            */

            'variants.*.media' => [
                'sometimes',
                'nullable',
                'array',
            ],

            'variants.*.media.*.id' => [
                'sometimes',
                'nullable',
                'integer',
            ],

            'variants.*.media.*.url' => [
                'required',
                'string',
            ],

            'variants.*.media.*.alt' => [
                'nullable',
                'string',
                'max:255',
            ],

            'variants.*.media.*.position' => [
                'nullable',
                'integer',
                'min:0',
            ],

            /*
            |------------------------------------------------------------------
            | Product-Level Media
            |------------------------------------------------------------------
            */

            'media' => [
                'sometimes',
                'nullable',
                'array',
            ],

            'media.*.id' => [
                'sometimes',
                'nullable',
                'integer',
            ],

            'media.*.url' => [
                'required',
                'string',
            ],

            'media.*.alt' => [
                'nullable',
                'string',
                'max:255',
            ],

            'media.*.position' => [
                'nullable',
                'integer',
                'min:0',
            ],

            /*
            |------------------------------------------------------------------
            | Tags
            |------------------------------------------------------------------
            |
            | Products reference tags by integer ID only.
            | Tag creation and translation management are handled by the
            | dedicated tag management API, not by product endpoints.
            | Absence of this key = no change to existing tag assignments.
            | Sending an empty array = detach all tags from this product.
            |------------------------------------------------------------------
            */

            'tags' => [
                'sometimes',
                'nullable',
                'array',
            ],

            'tags.*' => [
                'integer',
                'exists:tags,id',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateSkuUniqueness($validator);
            $this->validateVariantOptionAssignments($validator);
            $this->validateVariantDateConsistency($validator);
        });
    }

    /**
     * Bug #5 fix: Laravel's `distinct` rule treats multiple `null` SKUs as
     * duplicates of each other, which breaks auto-generated variants that
     * legitimately have no SKU yet. Only flag genuine duplicate non-empty SKUs.
     */
    private function validateSkuUniqueness($validator): void
    {
        $skus = collect($this->input('variants', []))
            ->pluck('sku')
            ->filter(fn ($sku) => is_string($sku) && trim($sku) !== '')
            ->map(fn ($sku) => strtolower(trim($sku)));

        $duplicates = $skus->duplicates();

        if ($duplicates->isNotEmpty()) {
            $validator->errors()->add(
                'variants',
                'Duplicate SKU(s): ' . $duplicates->unique()->implode(', '),
            );
        }
    }

    /**
     * Bug #12 fix: variants.*.options.* only validated value type, never that the
     * key is a real option name or the value is one of that option's defined
     * values. syncVariantOptionValues() silently skips mismatches, so bad data was
     * failing silently instead of surfacing here.
     */
    private function validateVariantOptionAssignments($validator): void
    {
        $options = collect($this->input('options', []));

        if ($options->isEmpty()) {
            return;
        }

        $allowedValuesByOption = $options->mapWithKeys(function ($option) {
            $name   = $option['name'] ?? null;
            $values = collect($option['values'] ?? [])->filter()->all();
            return [$name => $values];
        })->filter(fn ($values, $name) => $name !== null);

        foreach ($this->input('variants', []) as $index => $variant) {
            foreach (($variant['options'] ?? []) as $optionName => $optionValue) {
                if (!$allowedValuesByOption->has($optionName)) {
                    $validator->errors()->add(
                        "variants.{$index}.options.{$optionName}",
                        "Unknown option \"{$optionName}\" is not defined on this product.",
                    );
                    continue;
                }

                if (!in_array($optionValue, $allowedValuesByOption->get($optionName), true)) {
                    $validator->errors()->add(
                        "variants.{$index}.options.{$optionName}",
                        "\"{$optionValue}\" is not a defined value for option \"{$optionName}\".",
                    );
                }
            }
        }
    }

    /**
     * Bug #23 fix: replaces the static after_or_equal:variants.*.manufacture_date
     * rule, whose behavior when manufacture_date is null/absent was unverified and
     * risked rejecting a valid expiry-date-only variant. Mirrors the frontend's
     * validateProductStructure.ts, which only checks when BOTH dates are present.
     */
    private function validateVariantDateConsistency($validator): void
    {
        foreach ($this->input('variants', []) as $index => $variant) {
            $manufactureDate = $variant['manufacture_date'] ?? null;
            $expiryDate      = $variant['expiry_date'] ?? null;

            if (!$manufactureDate || !$expiryDate) {
                continue;
            }

            if (Carbon::parse($expiryDate)->lt(Carbon::parse($manufactureDate))) {
                $validator->errors()->add(
                    "variants.{$index}.expiry_date",
                    'Expiry date cannot be before manufacture date.',
                );
            }
        }
    }
}
