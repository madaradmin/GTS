<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assigned_to_user_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (strtolower((string) $value) === 'tics') {
                        return;
                    }

                    if (! is_numeric($value)) {
                        $fail('El técnico seleccionado no existe, no pertenece a GTS o se encuentra inactivo.');

                        return;
                    }

                    $exists = User::where('id', $value)
                        ->where('is_active', true)
                        ->whereHas('role', fn ($q) => $q->where('slug', 'gts'))
                        ->exists();

                    if (! $exists) {
                        $fail('El técnico seleccionado no existe, no pertenece a GTS o se encuentra inactivo.');
                    }
                },
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
