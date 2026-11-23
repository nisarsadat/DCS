<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaginateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'perPage' => 'sometimes|integer|min:1|max:200',
            'per_page' => 'sometimes|integer|min:1|max:200',
            'search' => 'sometimes|nullable|string',
            'order_column' => 'sometimes|string',
            'order_type' => 'sometimes|in:asc,desc',
        ];
    }
}
