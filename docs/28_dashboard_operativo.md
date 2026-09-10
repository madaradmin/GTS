# PASO 28: DASHBOARD OPERATIVO Y NAVEGACIÓN COMPLETA

## 1. OBJETIVO DEL PASO
Implementar el centro de comando visual y operativo (**Dashboard Táctico**) del sistema **GIT** (Gestión de Incidencias Tecnológicas) para el Centro de Operaciones Aeroportuarias (**APOC**) y el Grupo de Telecomunicaciones y Sistemas (**GTS**) del Aeropuerto Internacional Felipe Ángeles (**AIFA**). 

Este panel centraliza la telemetría operativa en tiempo real, proporcionando visibilidad integral de:
- **Métricas e Indicadores Clave de Desempeño (KPIs):** Total de incidencias activas, incidentes críticos, tiempo medio de resolución (**MTTR**) y tasa de cumplimiento de Acuerdos de Nivel de Servicio (**SLA**).
- **Control de Turnos Aeroportuarios:** Detección y visualización automática del turno de guardia activo (Matutino `06:00-14:00`, Vespertino `14:00-22:00`, Nocturno `22:00-06:00`).
- **Lista de Telemetría Reciente:** Grilla operacional con las últimas incidencias registradas con su folio táctico, severidad, subsistema y estado.
- **Cola de Atención Inmediata:** Despacho prioritario de contingencias críticas y altas para respuesta en tiempo crítico.
- **Distribución por Subsistemas:** Visualización de carga técnica distribuida entre infraestructura crítica (FIDS, BHS, CCTV, Redes/LAN, Telefonía IP, CUSS, Control de Accesos).

---

## 2. POR QUÉ LO HACEMOS
En un entorno aeroportuario de alta disponibilidad como el AIFA, los operadores de APOC y supervisores de GTS no pueden depender de consultas dispersas o listas planas de incidentes para evaluar el estado de la infraestructura tecnológica. 

1. **Conciencia Situacional (Situational Awareness):** El supervisor de turno necesita identificar en menos de 3 segundos si una falla en BHS (Manejo de Equipajes) o FIDS (Pantallas de Vuelo) compromete la operación de los pasajeros o la puntualidad de las aeronaves.
2. **Priorización Táctica:** Las fallas de severidad Crítica y Alta deben presentarse en una cola diferenciada de "Atención Inmediata", evitando que queden ocultas detrás de incidencias menores.
3. **Telemetría de Servicio (MTTR y SLA):** Medir la eficiencia técnica de resolución permite detectar cuellos de botella en cuadrillas o fallas recurrentes de hardware/enlaces de fibra óptica.
4. **Gobierno y Roles:** Garantiza que tanto técnicos de campo (GTS), operadores (APOC) y supervisores directivos tengan una interfaz ergonómica, responsiva y adaptada a monitores de video wall en el centro de mando.

---

## 3. DEPENDENCIAS
- **Paso 12 a 15 (Base de Datos y Modelos):** Relaciones Eloquent entre `Incident`, `Category`, `Priority`, `Status`, `User` e `IncidentHistory`.
- **Paso 16 a 18 (Autenticación y Control de Acceso):** Middleware `auth` y políticas de acceso.
- **Paso 19 a 25 (Ciclo de Vida de Incidencias):** Estados `abierta`, `asignada`, `en_proceso`, `resuelta`, `cerrada` y folios autonumerados (`INC-YYYY-XXXX`).
- **Paso 26 (Auditoría):** Registros históricos con marcas temporales precisas para el cálculo de MTTR y duración de incidentes.
- **Componentes Blade y Tailwind CSS:** Tipografía monospace para folios, badges de estado aeroportuarios e iconos Material Symbols.

---

## 4. ESTADO ACTUAL
- Previo a este paso, la ruta `/dashboard` retornaba una vista básica de bienvenida de Laravel Breeze sin datos reales ni widgets tácticos.
- El ciclo de vida completo de incidencias (creación, asignación, diagnóstico, resolución y cierre con evidencias) ya se encuentra 100% operativo y probado.
- Toda la data requerida (categorías, prioridades, usuarios, historiales) existe en la base de datos PostgreSQL.

---

## 5. PLAN
1. **Desarrollar el Servicio de Telemetría (`DashboardService`):**
   - Extraer lógica de agregación y cálculo fuera del controlador.
   - Computar métricas globales: `active_incidents`, `critical_incidents`, `mttr_hours` y `sla_compliance`.
   - Implementar algoritmo de identificación de turno de guardia según el reloj del servidor.
   - Diseñar la cola de incidentes de "Atención Inmediata" priorizados por nivel de criticidad descendente.
   - Calcular la distribución porcentual de fallas por categoría/subsistema aeroportuario.
2. **Implementar el Controlador (`DashboardController`):**
   - Inyectar `DashboardService` y suministrar la data a la vista Blade.
3. **Actualizar la Ruta en `routes/web.php`:**
   - Vincular `Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')`.
4. **Diseñar la Vista Táctica (`resources/views/dashboard.blade.php`):**
   - Header operativo con chip de turno activo y reloj de monitoreo.
   - 4 Tarjetas de Métricas (KPIs) con códigos de color e iconos.
   - Grid dividido 65% / 35%:
     - 65%: Tabla de incidencias recientes con folios enlazados a su detalle interactivo.
     - 35%: Cola de Atención Inmediata y barras de distribución por subsistema técnico.
5. **Pruebas Automatizadas de Integración (`DashboardTest`):**
   - Validar acceso autenticado vs invitados.
   - Validar renderizado de métricas y cálculos matemáticos (MTTR, porcentajes).
   - Validar ordenamiento y filtrado de la cola crítica.
   - Validar correcta identificación de turnos de guardia.

---

## 6. ARCHIVOS INVOLUCRADOS
- `app/Services/DashboardService.php` (Nuevo)
- `app/Http/Controllers/DashboardController.php` (Nuevo)
- `routes/web.php` (Modificado)
- `resources/views/dashboard.blade.php` (Modificado/Reemplazado)
- `tests/Feature/DashboardTest.php` (Nuevo)
- `docs/28_dashboard_operativo.md` (Documentación del paso)

---

## 7. IMPLEMENTACIÓN

### 7.1 Servicio Táctico de Telemetría (`app/Services/DashboardService.php`)
Encapsula todas las operaciones de agregación y telemetría:
- `getMetrics()`: Extrae conteo de incidencias activas, críticas, MTTR en horas y porcentaje de SLA.
- `getCurrentShift()`: Determina si el turno actual es Matutino (06:00-14:00), Vespertino (14:00-22:00) o Nocturno (22:00-06:00).
- `getRecentIncidents(int $limit)`: Obtiene la lista de las últimas $limit incidencias con relaciones precargadas.
- `getImmediateAttentionQueue(int $limit)`: Filtra incidencias abiertas o en proceso con prioridad Crítica o Alta ordenadas por `priorities.level DESC`.
- `getCategoryDistribution()`: Agrupa la carga de trabajo por subsistema técnico de telecomunicaciones/sistemas del AIFA.
- `getActiveTechniciansCount()`: Total de técnicos GTS activos y disponibles en la plataforma.

### 7.2 Controlador del Dashboard (`app/Http/Controllers/DashboardController.php`)
Despacha los datos preparados por el servicio a la vista `dashboard.blade.php`.

### 7.3 Interfaz Visual Blade (`resources/views/dashboard.blade.php`)
- **Cabecera Operativa:** Indicador de estado "CENTRO DE OPERACIONES APOC / GTS", reloj sincronizado e indicador de turno activo (`Matutino / Vespertino / Nocturno`).
- **4 Tarjetas de Métricas:**
  1. *Incidencias Activas:* Total de tickets no resueltos.
  2. *Atención Crítica:* Fallas de severidad Crítica/Alta que amenazan la operación aeroportuaria.
  3. *MTTR Promedio:* Indicador de velocidad de restablecimiento de enlace/servicio.
  4. *Cumplimiento SLA:* Porcentaje global de cumplimiento de acuerdos de nivel de servicio.
- **Panel Principal (65%):** Tabla de telemetría de incidencias recientes con folio monoespaciado, título, subsistema, prioridad, estado y tiempo transcurrido (`diffForHumans`).
- **Panel Lateral Táctico (35%):**
  - *Cola de Atención Inmediata:* Botón directo "Atender" para contingencias en curso.
  - *Carga por Subsistema:* Barras de progreso de saturación para FIDS, BHS, CCTV, Redes, etc.
  - *Contador de Cuadrilla:* Técnicos GTS activos de guardia en el aeropuerto.

---

## 8. EXPLICACIÓN TÉCNICA PARA ESTUDIANTE TELECOM

### El Dashboard como Video Wall del NOC (Network Operations Center)
En ingeniería de telecomunicaciones (como en un NOC de Telmex, AT&T o en el centro de control de tráfico aéreo de SENEAM), la supervisión de redes y enlaces no se realiza revisando bitácoras de texto en bruto o líneas de consola SSH una por una. Se utilizan consolas de telemetría visual (**NMS - Network Management Systems**) como Grafana, Zabbix o PRTG.

El Dashboard de GIT actúa con los mismos principios que un NMS de telecomunicaciones:

1. **Métricas de Telemetría (MTTR y SLA):**
   - **MTTR (Mean Time to Repair / Resolve):** Es la métrica reina de disponibilidad en enlaces de telecomunicaciones. Representa el tiempo medio transcurrido entre la caída de un servicio ($t_0$) y su restablecimiento operativo ($t_{up}$).
     $$\text{MTTR} = \frac{\sum (t_{\text{resolución}} - t_{\text{apertura}})}{N_{\text{incidentes}}}$$
     En telecomunicaciones aeroportuarias, una fibra troncal cortada o un switch de borde apagado debe tener un MTTR mínimo para evitar demoras en despegues.
   - **SLA (Service Level Agreement):** Es el compromiso porcentual de confiabilidad. Si el SLA de la red troncal del AIFA exige un 99.9% de uptime o resolución en menos de 2 horas para fallas críticas, el indicador del dashboard refleja la tasa de cumplimiento del equipo de campo en tiempo real.

2. **Colas de Priorización Estricta (Strict Priority Queuing - PQ):**
   - En conmutación de paquetes (routers Cisco/Juniper), cuando el búfer de una interfaz se satura, el programador de colas (**Scheduler**) aplica *Quality of Service (QoS)* con colas de prioridad estricta: los paquetes de voz y video de control aéreo se despachan antes que el tráfico de datos regular.
   - En el Dashboard de GIT, la **Cola de Atención Inmediata** emula este programador: las fallas de prioridad `Crítica` (`level = 4`) y `Alta` (`level = 3`) ocupan la parte superior del despacho inmediato, asegurando que las cuadrillas de GTS las atiendan antes de solucionar requerimientos menores.

3. **Segmentación por Subsistemas (Carga de Tráfico por Dominio):**
   - Al igual que en un analizador de espectro o monitor de ancho de banda se mide qué porcentaje de canales RF o VLANs están saturados, la sección de **Carga por Subsistema** mide qué porcentaje de carga de fallas absorbe cada red física del aeropuerto (VLAN de Cámaras de Seguridad vs VLAN de Pantallas FIDS vs Red BHS).

---

## 9. PRUEBAS REALIZADAS
Se ejecutó la suite completa de pruebas unitarias y de integración mediante PHPUnit:
- `tests/Feature/DashboardTest.php`:
  1. `test_unauthenticated_user_is_redirected_to_login`: Verifica que ningún usuario no autenticado pueda acceder al dashboard.
  2. `test_authenticated_user_can_access_dashboard`: Verifica acceso exitoso `200 OK` para usuarios autenticados.
  3. `test_dashboard_displays_correct_metrics`: Valida cómputo exacto de incidencias activas y críticas.
  4. `test_dashboard_displays_recent_incidents`: Verifica que las incidencias recientes aparezcan en el grid principal.
  5. `test_immediate_attention_queue_shows_only_critical_and_high_incidents`: Asegura que incidentes de severidad Media o Baja no se filtren a la cola inmediata.
  6. `test_dashboard_service_computes_mttr_accurately`: Valida matemáticamente que una incidencia resuelta en 4 horas arroje exactamente un MTTR de 4.0 horas.
  7. `test_dashboard_displays_shift_information`: Verifica la detección dinámica del turno de guardia.
  8. `test_category_distribution_calculates_percentages_correctly`: Valida el desglose porcentual de carga por subsistemas (ej. 75% vs 25%).
  9. `test_inactive_user_cannot_access_dashboard`: Comprueba que usuarios con `is_active = false` sean rechazados.
  10. `test_dashboard_displays_active_technicians_count`: Valida el conteo de agentes técnicos GTS habilitados.

**Resultado de la Suite Completa:**
```
Tests: 129 passed (448 assertions)
Duration: 6.32s
Status: 100% OK
```

---

## 10. RESULTADO ESPERADO
- Al iniciar sesión con cualquier rol (APOC, GTS o Supervisor), el usuario es redirigido a `/dashboard`.
- La pantalla presenta un diseño táctico con fondo obscuro (`#0f172a`), tipografía legible, métricas de alto impacto, chip de turno y enlaces directos a cada incidente.
- Todas las consultas a la base de datos se ejecutan con *eager loading* (`with(['category', 'priority', 'status', 'assignedTo'])`), optimizando el rendimiento y evitando problemas de consultas $N+1$.

---

## 11. PROBLEMAS COMUNES Y SOLUCIONES
1. **Error en nombre de columna en `Priority`:**
   - *Problema:* La consulta original ordenaba por `weight DESC`, generando un error SQL de columna inexistente en PostgreSQL.
   - *Solución:* Se inspeccionó la migración `create_priorities_table`, confirmando que el nombre del campo numérico es `level`. Se actualizó la consulta en `DashboardService` a `->orderBy('priorities.level', 'desc')`.
2. **Cálculo de MTTR sin incidencias resueltas:**
   - *Problema:* Una división por cero cuando la base de datos inicia sin incidencias resueltas.
   - *Solución:* Se agregó una condición de guarda (`if ($resolvedIncidents->isEmpty()) return 0.0;`), garantizando estabilidad operativa.
3. **Manejo de zonas horarias en turnos de guardia:**
   - *Problema:* Desfase en el cálculo del turno si el servidor utiliza UTC en lugar de la hora local del aeropuerto (`America/Mexico_City`).
   - *Solución:* Configurado `Carbon::now()` respetando la zona horaria de la aplicación Laravel.

---

## 12. VERIFICACIÓN FINAL
1. Todos los archivos de servicio, controlador, rutas y vistas están creados y sincronizados.
2. 129 pruebas automáticas superadas con éxito (100% de la suite verde).
3. Base de datos PostgreSQL resembrada con catálogo y datos semilla consistentes.
4. Código limpio, comentado y estructurado según las mejores prácticas de Laravel.

---

**STOPPING.** Esperando la instrucción explícita del usuario: `"EJECUTA EL PASO 29"`.