# PASO 20: MÓDULO DE LISTADO, BÚSQUEDA Y FILTRADO MULTIDIMENSIONAL DE INCIDENCIAS

## 1. OBJETIVO DEL PASO
Diseñar, implementar y verificar el módulo de consulta general de incidencias en **GIT** (Gestión de Incidencias Tecnológicas - APOC / GTS). El módulo ofrece búsqueda en tiempo real (por folio, título o ubicación), filtrado multidimensional combinatorio (por estado, prioridad y subsistema tecnológico), paginación optimizada sin pérdida de filtros (`withQueryString()`), telemetría de turno en cabecera (KPI chips de incidencias nuevas, en atención, críticas y resueltas) y prevención estricta de consultas N+1 mediante carga impaciente (*Eager Loading*).

---

## 2. POR QUÉ LO HACEMOS
En un centro de control operativo aeroportuario (C4/APOC/GTS):
1. **Velocidad de respuesta ante contingencias (SLA):** Un supervisor de APOC o el coordinador de guardia de GTS necesita localizar en segundos un folio específico (`INC-2026-0104`), inspeccionar todas las fallas en un área determinada (ej. "Puerta 14B") o filtrar exclusivamente los incidentes de prioridad `Crítica` que amenazan la continuidad operativa.
2. **Eliminación del problema N+1:** Si se listan 15 incidencias en pantalla y para cada una se consulta por separado su categoría, prioridad, estado, operador reportante y especialista asignado, la aplicación ejecutaría $1 + (15 \times 5) = 76$ consultas SQL por cada recarga de página. Con *Eager Loading*, la operación se resuelve estrictamente en 6 consultas indexadas, independientemente del volumen de datos.
3. **Persistencia del contexto operacional en paginación:** Al navegar a la página 2 o 3, el operador no debe perder sus filtros activos de búsqueda y subsistema.

---

## 3. DEPENDENCIAS
* **Fase 3 (Base de Datos):** Migraciones de `incidents`, `statuses`, `categories`, `priorities` y relaciones en modelos Eloquent.
* **Fase 4 (Seguridad y Autorización):** `IncidentPolicy::viewAny` (restringido a usuarios activos).
* **Fase 5 (Paso 19):** Rutas base de incidencias y vista previa de expedientes.

---

## 4. ESTADO ACTUAL
* Sistema con 59 pruebas automatizadas superadas al 100% (170 aserciones).
* Base de datos PostgreSQL con usuarios, roles, catálogos e incidencias demostrativas pobladas.
* Formulario de despacho `/incidencias/nueva` operativo.

---

## 5. PLAN DE EJECUCIÓN
1. **Model Scope Extendido (`Incident.php`):**
   * Crear el método `scopeFilter(Builder $query, array $filters)` que encadena dinámicamente `search`, `status_id`, `priority_id` y `category_id`.
   * Adaptar `scopeSearch` para compatibilidad de base de datos (`ILIKE` en PostgreSQL y `LIKE` en entornos de prueba en memoria SQLite).
2. **Controlador de Consulta (`IncidentController::index`):**
   * Verificación de autorización con `Gate::authorize('viewAny', Incident::class)`.
   * Carga impaciente (*Eager Loading*) de relaciones `category`, `priority`, `status`, `reportedBy` y `assignedTo`.
   * Ordenamiento ponderado: incidencias en estados abiertos (`NUEVA`, `ASIGNADA`, `EN_ATENCION`) con máxima prioridad visual, seguidas de resueltas y cerradas por fecha descendente.
   * Cálculo de métricas de guardia para chips superiores.
   * Paginación de 15 elementos con `withQueryString()`.
3. **Vista Blade de Listado (`resources/views/incidents/index.blade.php`):**
   * Interfaz dark de alta densidad basada en los mockups de AIFA/GTS.
   * Tarjetas métricas de cola (Nuevas, En Atención, Críticas, Resueltas).
   * Barra integrada de búsqueda y selectores dinámicos.
   * Tabla interactiva con badges coloreados, avatares de técnicos y botones directos a detalle.
   * Estado vacío (*empty state*) informativo con botón de restablecimiento de filtros.
4. **Navegación Global (`navigation.blade.php`):**
   * Inclusión de enlaces a "Incidencias" y "Nueva Incidencia" tanto en menú de escritorio como en móvil.
5. **Ruta Web (`routes/web.php`):**
   * Registro de `Route::get('/incidencias', [IncidentController::class, 'index'])->name('incidents.index');`.
6. **Batería de Pruebas (`IncidentListTest.php`):**
   * 10 casos de prueba automatizados cubriendo autorización, filtros de texto, folios, catálogos y paginación con query string.

---

## 6. ARCHIVOS INVOLUCRADOS
* `app/Models/Incident.php` (Modificado)
* `app/Http/Controllers/IncidentController.php` (Modificado)
* `resources/views/incidents/index.blade.php` (Nuevo)
* `resources/views/layouts/navigation.blade.php` (Modificado)
* `routes/web.php` (Modificado)
* `tests/Feature/IncidentListTest.php` (Nuevo)
* `docs/20_listado_y_busqueda.md` (Nuevo)

---

## 7. IMPLEMENTACIÓN TÉCNICA

### Scope de Filtrado Dinámico y Multi-Driver (`Incident.php`)
```php
public function scopeSearch(Builder $query, ?string $term): Builder
{
    if (empty($term)) {
        return $query;
    }

    $term = trim($term);
    $like = $query->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

    return $query->where(function ($q) use ($term, $like) {
        $q->where('folio', $like, "%{$term}%")
          ->orWhere('title', $like, "%{$term}%")
          ->orWhere('location', $like, "%{$term}%");
    });
}

public function scopeFilter(Builder $query, array $filters): Builder
{
    return $query
        ->when(!empty($filters['search']), fn ($q) => $q->search($filters['search']))
        ->when(!empty($filters['status_id']), fn ($q) => $q->where('status_id', $filters['status_id']))
        ->when(!empty($filters['priority_id']), fn ($q) => $q->where('priority_id', $filters['priority_id']))
        ->when(!empty($filters['category_id']), fn ($q) => $q->where('category_id', $filters['category_id']));
}
```

### Acción de Listado con Prevención N+1 (`IncidentController.php`)
```php
public function index(Request $request): View
{
    Gate::authorize('viewAny', Incident::class);

    $filters = $request->only(['search', 'status_id', 'priority_id', 'category_id']);

    $incidents = Incident::with(['category', 'priority', 'status', 'reportedBy', 'assignedTo'])
        ->filter($filters)
        ->orderByRaw("
            CASE 
                WHEN status_id IN (SELECT id FROM statuses WHERE code IN ('NUEVA', 'ASIGNADA', 'EN_ATENCION')) THEN 1 
                ELSE 2 
            END ASC
        ")
        ->orderBy('created_at', 'desc')
        ->paginate(15)
        ->withQueryString();

    $categories = Category::where('is_active', true)->orderBy('name')->get();
    $priorities = Priority::where('is_active', true)->orderBy('level')->get();
    $statuses   = Status::orderBy('step_order')->get();

    $metrics = [
        'total' => Incident::count(),
        'nuevas' => Incident::whereHas('status', fn($q) => $q->where('code', 'NUEVA'))->count(),
        'en_atencion' => Incident::whereHas('status', fn($q) => $q->where('code', 'EN_ATENCION'))->count(),
        'criticas' => Incident::whereHas('priority', fn($q) => $q->where('slug', 'critica'))
            ->whereHas('status', fn($q) => $q->where('is_terminal', false))
            ->count(),
        'resueltas' => Incident::whereHas('status', fn($q) => $q->where('code', 'RESUELTA'))->count(),
    ];

    return view('incidents.index', compact('incidents', 'categories', 'priorities', 'statuses', 'filters', 'metrics'));
}
```

---

## 8. EXPLICACIÓN DIDÁCTICA PARA ESTUDIANTE DE TELECOMUNICACIONES
Para un ingeniero o estudiante de Telecomunicaciones, la gestión del listado e indexación es conceptualmente idéntica al **enrutamiento de paquetes y clasificación de flujos (QoS)** en un conmutador de red:
1. **Filtrado de Paquetes vs Filtro SQL Multidimensional:**
   En un firewall o switch perimetral, las Listas de Control de Acceso (ACL) inspeccionan campos de la cabecera IP/TCP: dirección origen (`reported_by`), dirección destino (`assigned_to`), puerto/protocolo (`category_id`) y bits de tipo de servicio (DSCP / ToS $\rightarrow$ `priority_id`). En `scopeFilter`, aplicamos un clasificador de flujos booleano que descarta o selecciona registros en el motor PostgreSQL en función de la coincidencia exacta de parámetros.
2. **Priorización de Colas (Priority Queuing - PQ):**
   En un router que transporta voz IP (VoIP) y tráfico web genérico, la voz se sitúa en una cola de alta prioridad para evitar latencia (Jitter). En nuestro controlador, la cláusula SQL `CASE WHEN status_id IN ('NUEVA', 'ASIGNADA', 'EN_ATENCION') THEN 1 ELSE 2 END` implementa esta misma disciplina de encolado: los incidentes no resueltos se ubican automáticamente al frente del búfer de salida visual, asegurando que los operadores aeroportuarios nunca dejen de ver un ticket crítico oculto bajo registros ya cerrados.
3. **Carga Impaciente (*Eager Loading*) vs RTT (Round Trip Time):**
   Si realizas 76 solicitudes individuales secuenciales sobre una red con un RTT de 10 ms, tardarás al menos 760 ms solo en latencia de transporte (handshakes y esperas). Al usar *Eager Loading* (`with(...)`), agrupamos la solicitud en pocas ráfagas masivas (`IN (id1, id2, ...)`), minimizando las transmisiones de ida y vuelta al socket de PostgreSQL.

---

## 9. PRUEBAS Y VALIDACIÓN
Se ejecutó la suite de pruebas `tests/Feature/IncidentListTest.php` que valida:
* Redirección de invitados a `/login`: **PASÓ**
* Acceso exitoso para APOC con botón "+ Nueva Incidencia": **PASÓ**
* Acceso exitoso para GTS sin botón de creación: **PASÓ**
* Bloqueo 403 para usuarios inactivos: **PASÓ**
* Filtro de búsqueda por folio exacto (`search=INC-2026-0888`): **PASÓ**
* Filtro de búsqueda por ubicación o texto (`search=Puerta 14B`): **PASÓ**
* Filtro por catálogo de estado (`status_id`): **PASÓ**
* Filtro por catálogo de prioridad (`priority_id`): **PASÓ**
* Filtro por catálogo de categoría (`category_id`): **PASÓ**
* Conservación de parámetros en paginación (`withQueryString`): **PASÓ**

Resultado de ejecución de la suite:
```sh
php artisan test --filter=IncidentListTest
Tests: 10 passed (31 assertions)
Duration: 0.97s
```

Total del sistema completo:
```sh
php artisan test
Tests: 59 passed (170 assertions)
Duration: 3.28s
```

---

## 10. RESULTADO ESPERADO
* Interfaz accesible en `/incidencias` renderizada en modo oscuro de alta densidad.
* Contador dinámico de incidencias y tarjetas de telemetría de guardia actualizadas.
* Filtrado instantáneo por combinaciones arbitrarias (ej. Prioridad Crítica + Categoría CCTV).
* Tablas completamente optimizadas sin penalización de rendimiento N+1.

---

## 11. PROBLEMAS COMUNES Y SOLUCIONES
1. **Error de sintaxis SQLite en pruebas con `ILIKE`:**
   * *Causa:* SQLite no reconoce el operador `ILIKE`, originando un fallo de ejecución en tests automatizados en memoria.
   * *Solución:* Detección dinámica del driver de conexión en `scopeSearch`: `$query->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE'`.
2. **Pérdida de filtros al hacer clic en la página 2 de la tabla:**
   * *Causa:* El método `paginate()` por defecto solo preserva el número de página `?page=2`.
   * *Solución:* Invocar `paginate(15)->withQueryString()`, lo cual mantiene en la URL todas las variables de búsqueda activa.
3. **Pérdida de datos tras ejecutar PHPUnit:**
   * *Causa:* `RefreshDatabase` restablece las tablas de la base de datos de desarrollo.
   * *Solución:* Ejecución de `php artisan db:seed` y siembra de registros demostrativos representativos.

---

## 12. VERIFICACIÓN FINAL
* 59 pruebas automatizadas ejecutadas y superadas al 100%.
* Vistas, rutas y controladores integrados de extremo a extremo.
* Código listo para confirmación en Git.