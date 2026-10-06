<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Muestra la interfaz de telemetría y gestión de infraestructura tecnológica de GTS.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Category::class);

        $filters = $request->only(['search', 'category', 'status']);

        // 1. Telemetría de Infraestructura Operacional
        $totalInfrastructure = Category::count();
        $activeInfrastructure = Category::where('status', 'ACTIVO')->count();
        $outOfService = Category::where('status', 'FUERA DE SERVICIO')->count();
        $inactiveInfrastructure = Category::where('status', 'INACTIVO')->count();

        $availabilityRate = $totalInfrastructure > 0
            ? round(($activeInfrastructure / $totalInfrastructure) * 100, 1)
            : 100.0;

        // 2. Gráficas Operativas (Máximo 3, respondiendo preguntas de ingeniería y soporte)
        // Gráfica 1: Distribución por categoría / subsistema
        $categoriesList = Category::orderBy('name')->get();
        $distributionData = $categoriesList->map(function (Category $cat) use ($totalInfrastructure) {
            $percentage = $totalInfrastructure > 0 ? round((1 / $totalInfrastructure) * 100, 1) : 0;

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'color' => $cat->color ?: '#06B6D4',
                'icon' => $cat->icon ?: 'hub',
                'percentage' => $percentage,
            ];
        });

        // Gráfica 2: Estado de Salud del Parque Tecnológico (Activos vs Fuera de Servicio vs Inactivos)
        $statusBreakdown = [
            'activo' => [
                'label' => 'Activos Operacionales',
                'count' => $activeInfrastructure,
                'percentage' => $totalInfrastructure > 0 ? round(($activeInfrastructure / $totalInfrastructure) * 100, 1) : 0,
                'color' => '#10B981',
            ],
            'fuera_servicio' => [
                'label' => 'Fuera de Servicio',
                'count' => $outOfService,
                'percentage' => $totalInfrastructure > 0 ? round(($outOfService / $totalInfrastructure) * 100, 1) : 0,
                'color' => '#D83B52',
            ],
            'inactivo' => [
                'label' => 'Inactivos / Mant.',
                'count' => $inactiveInfrastructure,
                'percentage' => $totalInfrastructure > 0 ? round(($inactiveInfrastructure / $totalInfrastructure) * 100, 1) : 0,
                'color' => '#707B8A',
            ],
        ];

        // Gráfica 3: Incidencias Asociadas por Infraestructura (Históricas y Activas)
        $incidentsByInfrastructure = $categoriesList->map(function (Category $cat) {
            $totalInc = $cat->incidents()->count();
            $activeInc = $cat->activeIncidents()->count();

            return [
                'name' => $cat->name,
                'color' => $cat->color ?: '#3D82C6',
                'total' => $totalInc,
                'active' => $activeInc,
                'resolved' => max(0, $totalInc - $activeInc),
            ];
        });

        // 3. Consulta y filtrado de la tabla de inventario
        $query = Category::withCount(['incidents', 'activeIncidents']);

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%")
                    ->orWhere('location', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if (! empty($filters['category'])) {
            $query->where('slug', $filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $infrastructureItems = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('catalogs.index', compact(
            'totalInfrastructure',
            'activeInfrastructure',
            'outOfService',
            'inactiveInfrastructure',
            'availabilityRate',
            'distributionData',
            'statusBreakdown',
            'incidentsByInfrastructure',
            'infrastructureItems',
            'categoriesList',
            'filters'
        ));
    }

    /**
     * Da de alta un nuevo componente de infraestructura o subsistema en el catálogo.
     * Exclusivo para Administradores.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Category::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'code' => ['nullable', 'string', 'max:50', 'unique:categories,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
            'technical_specs' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:ACTIVO,INACTIVO,FUERA DE SERVICIO'],
        ]);

        $slug = Str::slug($validated['name']);
        // Asegurar unicidad del slug
        $baseSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $code = ! empty($validated['code'])
            ? strtoupper(trim($validated['code']))
            : 'INF-'.strtoupper(Str::limit(Str::slug($validated['name'], ''), 6, ''));

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'code' => $code,
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'technical_specs' => $validated['technical_specs'] ?? null,
            'icon' => $validated['icon'] ?: 'hub',
            'color' => $validated['color'] ?: '#06B6D4',
            'status' => $validated['status'],
        ]);

        return redirect()->route('catalogs.index')->with('success', "Recurso de infraestructura '{$category->name}' dado de alta exitosamente.");
    }

    /**
     * Actualiza la información técnica y metadatos de un recurso.
     * Exclusivo para Administradores.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name,'.$category->id],
            'code' => ['nullable', 'string', 'max:50', 'unique:categories,code,'.$category->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
            'technical_specs' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:ACTIVO,INACTIVO,FUERA DE SERVICIO'],
        ]);

        $code = ! empty($validated['code'])
            ? strtoupper(trim($validated['code']))
            : $category->code;

        $category->update([
            'name' => $validated['name'],
            'code' => $code,
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'technical_specs' => $validated['technical_specs'] ?? null,
            'icon' => $validated['icon'] ?: $category->icon,
            'color' => $validated['color'] ?: $category->color,
            'status' => $validated['status'],
        ]);

        return redirect()->route('catalogs.index')->with('success', "Información técnica de '{$category->name}' actualizada correctamente.");
    }

    /**
     * Cambia el estado operativo ("Dar de baja" / poner fuera de servicio / activar).
     * No elimina físicamente el registro para mantener la trazabilidad histórica.
     * Exclusivo para Administradores.
     */
    public function updateStatus(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('toggleStatus', $category);

        $validated = $request->validate([
            'status' => ['required', 'in:ACTIVO,INACTIVO,FUERA DE SERVICIO'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $oldStatus = $category->status;
        $category->status = $validated['status'];
        $category->save();

        $actionWord = match ($validated['status']) {
            'FUERA DE SERVICIO' => 'puesto FUERA DE SERVICIO',
            'INACTIVO' => 'dado de baja temporalmente',
            'ACTIVO' => 'reincorporado a servicio ACTIVO',
        };

        return redirect()->route('catalogs.index')->with('success', "El recurso '{$category->name}' ha sido {$actionWord} exitosamente.");
    }

    /**
     * Elimina físicamente un recurso solo si NO cuenta con incidencias vinculadas.
     * Exclusivo para Administradores.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $incidentCount = $category->incidents()->count();

        if ($incidentCount > 0) {
            return redirect()->route('catalogs.index')->with('error', "Acción denegada por integridad referencial: El recurso '{$category->name}' cuenta con {$incidentCount} incidencias históricas vinculadas. Para retirarlo del servicio operativo, utilice la opción 'Dar de baja' cambiando su estado a 'FUERA DE SERVICIO' o 'INACTIVO'.");
        }

        Gate::authorize('delete', $category);

        $name = $category->name;
        $category->delete();

        return redirect()->route('catalogs.index')->with('success', "El recurso '{$name}' ha sido eliminado físicamente del catálogo.");
    }
}
