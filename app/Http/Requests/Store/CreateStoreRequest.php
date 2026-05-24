<?php

namespace App\Http\Requests\Store;

use App\Rules\ReservedOrBlockedSlug;
use Illuminate\Foundation\Http\FormRequest;

class CreateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:stores,slug', 'regex:/^[a-z0-9-]+$/', new ReservedOrBlockedSlug()],
        ];
    }
}
