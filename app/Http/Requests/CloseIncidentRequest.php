<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CloseIncidentRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para dar visto bueno y cerrar la incidencia.
     */
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return $incident
            ? $this->user()->can('close', $incident)
            : ($this->user()->isApoc() || $this->user()->isGts() || $this->user()->isAdmin());
    }

    /**
     * Reglas de validación para el cierre formal de la incidencia.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comment' => [
                'nullable',
                'string',
                'max:1000',
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
            'comment.max' => 'Las observaciones de cierre no pueden exceder los 1000 caracteres.',
        ];
    }
}
