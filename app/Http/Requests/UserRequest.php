<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function prePareForValidation()
    {
        $status = $this->input("status");
        if (is_string($status)) {
            $normalized = strtolower($status);
            if (in_array($normalized, ["true", "1"], true)) {
                $status = 1;
            } elseif (in_array($normalized, ["false", "0"], true)) {
                $status = 0;
            }
        }

        return $this->merge([
            "first_name" => $this->input("firstName"),
            "last_name" => $this->input("lastName"),
            "role_id" => $this->input("roleId"),
            "status" => $status,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => 'required|string|max:15',
            'password' => $this->route('user')
                ? ['nullable', 'string', 'min:8']
                : ['required', 'string', 'min:8'],
            'status' => 'required|boolean',
            User::COLUMN_PROFILE_PICTURE => ['nullable'],
            'email' => [
                'required',
                'email',
                Rule::unique("roles", "name")->ignore($this->route("role") ? $this->route("role")->id : null),            ],
            'role_id' => 'required|numeric|exists:roles,id',
        ];
    }
}