<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RejectResolutionRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para rechazar la solución.
     */
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return $incident
            ? $this->user()->can('rejectResolution', $incident)
            : ($this->user()->isApoc() || $this->user()->isAdmin());
    }

    /**
     * Reglas de validación para rechazo de solución por APOC.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => [
                'required',
                'string',
                'min:10',
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
            'rejection_reason.required' => 'Debe indicar el motivo por el cual la solución fue rechazada.',
            'rejection_reason.min' => 'El motivo de rechazo debe contener al menos 10 caracteres explicando la persistencia de la falla.',
            'rejection_reason.max' => 'El motivo de rechazo no puede superar los 1000 caracteres.',
        ];
    }
}
