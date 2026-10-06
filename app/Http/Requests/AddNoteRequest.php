<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddNoteRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para agregar notas técnicas.
     */
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return $incident
            ? $this->user()->can('addNote', $incident)
            : ($this->user()->isGts() || $this->user()->isAdmin());
    }

    /**
     * Reglas de validación para agregar notas técnicas de campo.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'note' => [
                'required',
                'string',
                'min:5',
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
            'note.required' => 'El contenido de la nota técnica es obligatorio.',
            'note.min' => 'La nota técnica debe contener al menos 5 caracteres.',
            'note.max' => 'La nota técnica no puede superar los 1000 caracteres.',
        ];
    }
}
