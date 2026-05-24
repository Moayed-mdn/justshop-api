<?php

declare(strict_types=1);

namespace App\Actions\Store;

use App\DTOs\Store\SlugAvailabilityDTO;
use App\Models\Store;
use App\Rules\ReservedOrBlockedSlug;
use Illuminate\Support\Facades\Validator;

class CheckSlugAvailabilityAction
{
    public function execute(string $slug): SlugAvailabilityDTO
    {
        if (empty($slug)) {
            return new SlugAvailabilityDTO(available: false, reason: 'empty');
        }

        // Check for reserved or blocked slugs using the custom rule
        $validator = Validator::make(['slug' => $slug], [
            'slug' => [new ReservedOrBlockedSlug()],
        ]);

        if ($validator->fails()) {
            // Extract the specific reason from the custom rule
            $messages = $validator->errors()->get('slug');
            if (in_array('The slug is reserved.', $messages, true)) {
                return new SlugAvailabilityDTO(available: false, reason: 'reserved');
            }
            if (in_array('The slug contains blocked words.', $messages, true)) {
                return new SlugAvailabilityDTO(available: false, reason: 'blocked');
            }
            // Fallback for other potential regex or format issues in the future
            return new SlugAvailabilityDTO(available: false, reason: 'invalid');
        }

        $isTaken = Store::where('slug', $slug)->exists();

        if ($isTaken) {
            return new SlugAvailabilityDTO(available: false, reason: 'taken');
        }

        return new SlugAvailabilityDTO(available: true, reason: null);
    }
}
