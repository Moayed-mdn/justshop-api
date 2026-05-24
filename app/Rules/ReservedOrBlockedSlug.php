<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ReservedOrBlockedSlug implements ValidationRule
{
    private array $reservedSlugs = [
        'admin', 'app', 'api', 'auth', 'blog', 'cdn', 'dashboard', 'docs', 'faq', 'home',
        'login', 'logout', 'me', 'metrics', 'oauth', 'pages', 'profile', 'register',
        'root', 'setup', 'settings', 'store', 'support', 'system', 'user', 'users',
        'v1', 'v2', 'web', 'www',
    ];

    private array $blockedWords = [
        'fuck', 'shit', 'cunt', 'pussy', 'asshole', 'bitch', 'whore', 'nigger',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $slug = strtolower((string) $value);

        if (in_array($slug, $this->reservedSlugs, true)) {
            $fail('The :attribute is reserved.');
        }

        foreach ($this->blockedWords as $blockedWord) {
            if (str_contains($slug, $blockedWord)) {
                $fail('The :attribute contains blocked words.');

                return;
            }
        }
    }
}
