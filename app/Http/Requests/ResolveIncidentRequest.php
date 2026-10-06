<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResolveIncidentRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para resolver la incidencia.
     */
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return $incident
            ? $this->user()->can('resolve', $incident)
            : ($this->user()->isGts() || $this->user()->isAdmin());
    }

    /**
     * Reglas de validación para marcar una incidencia como resuelta.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'solution_description' => [
                'required',
                'string',
                'min:15',
                'max:3000',
            ],
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
            'solution_description.required' => 'La descripción de la solución técnica aplicada es obligatoria.',
            'solution_description.min' => 'La solución técnica debe contener al menos 15 caracteres descriptivos del arreglo realizado.',
            'solution_description.max' => 'La descripción de la solución no puede exceder los 3000 caracteres.',
        ];
    }
}
