<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_active;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $isAdmin = $user && $user->isAdmin();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user?->id),
            ],
        ];

        if ($isAdmin) {
            $rules['username'] = [
                'required',
                'string',
                'lowercase',
                'max:50',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique(User::class)->ignore($user?->id),
            ];
        } else {
            $rules['username'] = [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($user) {
                    if (! is_null($value) && $user && strtolower($value) !== strtolower($user->username)) {
                        $fail('No está autorizado para modificar su nombre de usuario institucional.');
                    }
                },
            ];
        }

        return $rules;
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.regex' => 'El nombre de usuario sólo puede contener letras, números, puntos, guiones y guiones bajos.',
        ];
    }
}
