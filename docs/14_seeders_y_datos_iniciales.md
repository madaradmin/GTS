# SEEDERS Y DATOS INICIALES
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**Base de Datos:** PostgreSQL 18.6 (`git_incidentes`)  
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. DATOS INICIALES POBLADOS EN EL SISTEMA

#### A. Catálogo de Roles (`RoleSeeder`)
* `apoc` — Operador APOC (Centro de Operaciones Aeroportuarias).
* `gts` — Especialista GTS (Grupo de Seguridad Tecnológica).
* `admin` — Administrador del Sistema.

#### B. Cuentas de Usuario Demostrativas (`UserSeeder`)
| Nombre | Correo Institucional | Contraseña | Rol Asignado | Gafete / Badge |
|---|---|---|---|---|
| **Tco. M. Reyes** | `apoc@aifa.aero` | `Password123!` | APOC | `APOC-042` |
| **Ing. Roberto Morales** | `gts.morales@aifa.aero` | `Password123!` | GTS | `GTS-108` |
| **Ing. Carlos Mendoza** | `gts.mendoza@aifa.aero` | `Password123!` | GTS | `GTS-215` |
| **Admin Seguridad Tecnológica** | `admin@aifa.aero` | `Password123!` | ADMIN | `ADM-001` |

#### C. Subsistemas Tecnológicos Aeroportuarios (`CategorySeeder`)
* `cctv` — CCTV y Videovigilancia (`#06B6D4`, icono `videocam`)
* `access-control` — Control de Acceso (`#8B5CF6`, icono `fingerprint`)
* `fids` — FIDS / Pantallas de Vuelo (`#F59E0B`, icono `tv`)
* `tetra` — Radiocomunicación TETRA (`#3B82F6`, icono `radio`)
* `networks` — Redes e Infraestructura IT (`#10B981`, icono `hub`)

#### D. Prioridades y Acuerdos de Nivel de Servicio (`PrioritySeeder`)
* `baja` (Nivel 1, `#64748B`, SLA 240 min)
* `media` (Nivel 2, `#3B82F6`, SLA 120 min)
* `alta` (Nivel 3, `#F59E0B`, SLA 45 min)
* `critica` (Nivel 4, `#EF4444`, SLA 15 min)

#### E. Estados del Ciclo de Vida FSM (`StatusSeeder`)
* `NUEVA` (Paso 1, `#3B82F6`)
* `ASIGNADA` (Paso 2, `#8B5CF6`)
* `EN_ATENCION` (Paso 3, `#F59E0B`)
* `RESUELTA` (Paso 4, `#10B981`)
* `CERRADA` (Paso 5, `#64748B`, terminal)

#### F. Secuencia Anual de Folio (`FolioSequenceSeeder`)
* Inicializada en el año en curso para dar continuidad correlativa a las incidencias (`INC-YYYY-XXXX`).

