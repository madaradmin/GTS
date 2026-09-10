<?php

namespace App\Http\Requests;

use App\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para crear una incidencia.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('create', Incident::class);
    }

    /**
     * Reglas de validación para la creación de una incidencia.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'location' => ['required', 'string', 'min:3', 'max:255'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'priority_id' => [
                'required',
                'integer',
                Rule::exists('priorities', 'id')->where('is_active', true),
            ],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
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
            'title.required' => 'El título o asunto de la incidencia es obligatorio.',
            'title.min' => 'El título debe contener al menos 5 caracteres.',
            'title.max' => 'El título no puede exceder los 255 caracteres.',
            'description.required' => 'La descripción detallada del problema es obligatoria.',
            'description.min' => 'La descripción debe contener al menos 10 caracteres explicativos.',
            'location.required' => 'La ubicación o área afectada en el aeropuerto es obligatoria.',
            'category_id.required' => 'Debe seleccionar un subsistema tecnológico válido.',
            'category_id.exists' => 'El subsistema seleccionado no es válido o está inactivo.',
            'priority_id.required' => 'Debe seleccionar un nivel de prioridad.',
            'priority_id.exists' => 'El nivel de prioridad seleccionado no es válido.',
            'attachments.max' => 'No puede adjuntar más de 5 archivos simultáneamente.',
            'attachments.*.mimes' => 'Solo se admiten evidencias con formato: JPG, PNG, PDF o TXT.',
            'attachments.*.max' => 'Cada archivo adjunto no debe superar los 10 MB.',
        ];
    }
}

