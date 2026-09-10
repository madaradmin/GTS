<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignIncidentRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para asignar especialistas.
     */
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return $incident
            ? $this->user()->can('assign', $incident)
            : ($this->user()->isGts() || $this->user()->isAdmin());
    }

    /**
     * Reglas de validación para asignación de incidencia.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assigned_to_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('is_active', true)
                          ->whereIn('role_id', function ($sub) {
                              $sub->select('id')->from('roles')->where('slug', 'gts');
                          });
                }),
            ],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'assigned_to_user_id.required' => 'Debe seleccionar un especialista GTS para la asignación.',
            'assigned_to_user_id.exists' => 'El técnico seleccionado no existe, no pertenece a GTS o se encuentra inactivo.',
            'comment.max' => 'El comentario de asignación no debe exceder los 500 caracteres.',
        ];
    }
}
