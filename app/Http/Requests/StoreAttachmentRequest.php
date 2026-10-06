<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para adjuntar evidencias.
     */
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return $incident && $this->user()->can('uploadAttachment', $incident);
    }

    /**
     * Reglas de validación para la carga de evidencias de soporte.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attachments' => ['required', 'array', 'min:1', 'max:5'],
            'attachments.*' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf,txt',
                'max:10240', // 10 MB máximo por archivo
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
            'attachments.required' => 'Debe seleccionar al menos un archivo de evidencia.',
            'attachments.array' => 'El formato de carga de archivos no es válido.',
            'attachments.min' => 'Debe adjuntar al menos 1 archivo de evidencia.',
            'attachments.max' => 'No puede adjuntar más de 5 archivos simultáneamente.',
            'attachments.*.required' => 'El archivo seleccionado es obligatorio.',
            'attachments.*.file' => 'El elemento cargado debe ser un archivo válido.',
            'attachments.*.mimes' => 'Solo se admiten evidencias con extensión: JPG, PNG, PDF o TXT.',
            'attachments.*.max' => 'Cada archivo de evidencia no debe superar los 10 MB.',
        ];
    }
}
