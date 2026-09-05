<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform\Orders;

use Illuminate\Foundation\Http\FormRequest;

class RefundPlatformOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0.01'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
