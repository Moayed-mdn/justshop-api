<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class FilterOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string'],
            'date_range' => ['sometimes', 'string'],
            'sort_by' => ['sometimes', 'string'],
        ];
    }
}