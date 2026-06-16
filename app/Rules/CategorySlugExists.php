<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Validates that a category slug exists in either the categories table
 * or the category_translations table (for localized slugs).
 *
 * This rule supports multi-lingual category slugs:
 * - Base slugs: categories.slug (e.g., 'fashion')
 * - Localized slugs: category_translations.slug (e.g., 'alazyaaa' for Arabic)
 */
class CategorySlugExists implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || empty($value)) {
            return;
        }

        // Single optimized query using UNION to check both tables
        $exists = DB::selectOne("
            SELECT 1 FROM (
                SELECT slug FROM categories WHERE slug = ?
                UNION ALL
                SELECT slug FROM category_translations WHERE slug = ?
            ) AS combined_slugs
            LIMIT 1
        ", [$value, $value]);

        if (!$exists) {
            $fail('The selected category slug is invalid.');
        }
    }
}
