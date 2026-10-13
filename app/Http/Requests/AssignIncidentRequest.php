<?php

namespace App\Http\Requests;

use App\Models\Role;
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
            : ($this->user()?->hasPermission('incidents.assign') || $this->user()?->isGts() || $this->user()?->isAdmin());
    }

    /**
     * Normalizar datos de entrada antes de ejecutar las reglas de validación.
     */
    protected function prepareForValidation(): void
    {
        // 1. Compatibilidad retroactiva con llamadas legadas y tests que envían únicamente 'assigned_to_user_id'
        if (! $this->has('areas')) {
            $legacyValue = $this->input('assigned_to_user_id');
            if (! empty($legacyValue)) {
                if (strtolower((string) $legacyValue) === 'tics') {
                    $this->merge([
                        'areas' => ['tics'],
                        'assigned_to_user_id' => null,
                    ]);
                } else {
                    $user = User::with('role')->find($legacyValue);
                    $assignerRoleIds = $this->user()?->assignableRoleIds() ?? [];
                    if ($user && $user->role_id && (empty($assignerRoleIds) || in_array($user->role_id, $assignerRoleIds, true) || $this->user()?->isAdmin())) {
                        $this->merge(['areas' => [$user->role->slug]]);
                    } else {
                        // Si no pertenece a sus alcances, asignar 'gts' por defecto para que la falla se concentre en assigned_to_user_id
                        $this->merge(['areas' => ['gts']]);
                    }
                }
            }
        }

        // 2. Normalizar y deduplicar array de áreas si fueron enviadas
        if ($this->has('areas')) {
            $rawAreas = $this->input('areas');
            $areas = is_array($rawAreas)
                ? array_values(array_unique(array_filter(array_map('trim', $rawAreas))))
                : array_filter([trim((string) $rawAreas)]);
            $this->merge(['areas' => $areas]);
        }

        // 3. Normalizar assigned_to_user_id si es string vacío o 'tics'
        $techId = $this->input('assigned_to_user_id');
        if ($techId === '' || $techId === null || strtolower((string) $techId) === 'tics') {
            $this->merge(['assigned_to_user_id' => null]);
        }
    }

    /**
     * Reglas de validación para asignación de incidencia.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'areas' => [
                'required_without:assigned_to_user_id',
                'array',
                'min:1',
            ],
            'areas.*' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $code = strtolower(trim((string) $value));

                    // 1. TICS no es un rol funcional válido para reasignación
                    if ($code === 'tics') {
                        $fail('El área TICS no es un destino válido para la asignación de incidencias.');

                        return;
                    }

                    // 2. ADMINISTRADOR nunca debe ser un área de asignación
                    if ($code === 'admin') {
                        $fail('El rol Administrador no es un área válida para asignación de incidencias.');

                        return;
                    }

                    // 3. Excluir el rol que originó la incidencia
                    $incident = $this->route('incident');
                    $creatorRoleId = $incident?->reportedBy?->role_id;
                    $creatorRoleSlug = strtolower($incident?->reportedBy?->role?->slug ?? '');

                    if ($creatorRoleSlug && $code === $creatorRoleSlug) {
                        $fail("No se puede asignar la incidencia a su rol de procedencia ({$value}).");

                        return;
                    }

                    $user = $this->user();
                    if ($user && $user->isAdmin()) {
                        $query = Role::whereRaw('LOWER(slug) = ?', [$code])
                            ->where('slug', '!=', 'admin');
                        if ($creatorRoleId) {
                            $query->where('id', '!=', $creatorRoleId);
                        }
                        if (! $query->exists()) {
                            $fail("El área seleccionada ({$value}) no es válida o no está autorizada.");
                        }

                        return;
                    }

                    $assignableRoleIds = $user?->assignableRoleIds() ?? [];
                    $query = Role::whereRaw('LOWER(slug) = ?', [$code])
                        ->where('slug', '!=', 'admin');

                    if ($creatorRoleId) {
                        $query->where('id', '!=', $creatorRoleId);
                    }

                    if (! empty($assignableRoleIds)) {
                        $query->whereIn('id', $assignableRoleIds);
                    } else {
                        $query->where('slug', 'gts');
                    }

                    if (! $query->exists()) {
                        $fail("No tiene autorización para asignar al área seleccionada ({$value}).");
                    }
                },
            ],
            'assigned_to_user_id' => [
                'required_without:areas',
                'nullable',
                function ($attribute, $value, $fail) {
                    if (empty($value) || strtolower((string) $value) === 'tics') {
                        return;
                    }

                    if (! is_numeric($value)) {
                        $fail('El técnico seleccionado no existe, no pertenece a un rol asignable o se encuentra inactivo.');

                        return;
                    }

                    $user = $this->user();
                    $technician = User::where('id', $value)->where('is_active', true)->with('role')->first();

                    if (! $technician) {
                        $fail('El técnico seleccionado no existe, no pertenece a un área asignable o se encuentra inactivo.');

                        return;
                    }

                    // A. El rol Administrador no puede ser seleccionado como especialista
                    if ($technician->isAdmin() || $technician->role?->slug === 'admin') {
                        $fail('El rol Administrador no puede ser seleccionado como especialista técnico.');

                        return;
                    }

                    // B. Excluir usuarios pertenecientes al rol creador de la incidencia
                    $incident = $this->route('incident');
                    $creatorRoleId = $incident?->reportedBy?->role_id;
                    if ($creatorRoleId && $technician->role_id === $creatorRoleId) {
                        $fail('No se puede asignar un técnico perteneciente al rol que originó la incidencia.');

                        return;
                    }

                    // C. Validar que el rol del técnico pertenezca a los roles que el usuario puede asignar
                    if (! $user || ! $user->isAdmin()) {
                        $assignableRoleIds = $user?->assignableRoleIds() ?? [];
                        if (! empty($assignableRoleIds)) {
                            if (! in_array($technician->role_id, $assignableRoleIds, true)) {
                                $fail('El técnico seleccionado no pertenece a sus alcances de asignación autorizados.');

                                return;
                            }
                        } else {
                            if ($technician->role?->slug !== 'gts') {
                                $fail('El técnico seleccionado no pertenece a GTS.');

                                return;
                            }
                        }
                    }

                    // D. Validar que el rol del técnico coincida con al menos una de las áreas seleccionadas en 'areas' si se enviaron áreas
                    $rawAreas = $this->input('areas', []);
                    $selectedAreas = array_map('strtolower', (array) $rawAreas);
                    $technicianRoleSlug = strtolower($technician->role?->slug ?? '');

                    if (! empty($selectedAreas) && ! in_array($technicianRoleSlug, $selectedAreas, true)) {
                        $fail('El especialista seleccionado debe pertenecer a una de las áreas responsables elegidas.');
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
            'areas.required' => 'Debe seleccionar al menos un área responsable para la asignación.',
            'areas.required_without' => 'Debe seleccionar al menos un área responsable para la asignación.',
            'areas.min' => 'Debe seleccionar al menos un área responsable para la asignación.',
            'assigned_to_user_id.required' => 'Debe seleccionar un especialista GTS para la asignación.',
            'assigned_to_user_id.required_without' => 'Debe seleccionar un especialista GTS para la asignación.',
            'assigned_to_user_id.exists' => 'El técnico seleccionado no existe, no pertenece a GTS o se encuentra inactivo.',
            'comment.max' => 'El comentario de asignación no debe exceder los 500 caracteres.',
        ];
    }
}
