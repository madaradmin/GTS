<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isAdmin() || $this->user()->hasPermission('roles.edit'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $role = $this->route('role');
        $roleId = $role instanceof Role ? $role->id : $role;
        $isProtected = $role instanceof Role ? $role->is_protected : false;

        $rules = [
            'name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['required', 'string', Rule::in(array_keys(Role::getColorPresets()))],
        ];

        // Solo permitir editar slug si el rol no es protegido por el sistema
        if (! $isProtected) {
            $rules['slug'] = [
                'required',
                'string',
                'max:30',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('roles', 'slug')->ignore($roleId),
            ];
        }

        return $rules;
    }

    /**
     * Custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'El identificador (slug) debe contener solo letras minúsculas, números y guiones.',
            'slug.unique' => 'Este identificador de rol ya se encuentra registrado.',
            'color.in' => 'El color seleccionado no es válido dentro de la paleta institucional.',
        ];
    }

    /**
     * Preparar datos antes de validar.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge([
                'slug' => strtolower(trim($this->input('slug'))),
            ]);
        }
    }
}
