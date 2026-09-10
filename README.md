# GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
### Sistema Transaccional de Telemetría, Despacho y Ciclo de Vida de Incidencias
**Aeropuerto Internacional Felipe Ángeles (AIFA)**

---

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel Framework](https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Database](https://img.shields.io/badge/PostgreSQL-16%2B-336791?logo=postgresql&logoColor=white)](https://postgresql.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.4-38B2AC?logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Test Suite](https://img.shields.io/badge/Tests-149%20passed%20(570%20assertions)-success?logo=phpunit&logoColor=white)](https://phpunit.de)
[![Security Status](https://img.shields.io/badge/Security-Hardened%20(OWASP%20Top%2010)-blue?logo=shield&logoColor=white)](docs/29_pruebas_integrales_y_seguridad.md)

---

## 1. VISIÓN GENERAL Y CONTEXTO OPERATIVO

**GIT** es una plataforma web corporativa de alta disponibilidad diseñada específicamente para gestionar, despachar, auditar y resolver el 100% de las contingencias e incidencias tecnológicas que impactan la operación aeroportuaria del **AIFA (Aeropuerto Internacional Felipe Ángeles)**.

El sistema articula la interacción continua entre dos unidades operativas críticas:
- **APOC (Airport Operations Center):** Centro neurálgico que supervisa la continuidad operativa de pistas, plataformas, salas de última espera (SUE), mostradores y filtros. Reporta anomalías inmediatamente, monitorea los SLAs de atención y otorga el visto bueno final para el cierre administrativo.
- **GTS (Grupo de Telecomunicaciones y Sistemas):** Cuerpo técnico y cuadrillas de ingeniería en campo encargados de la atención, diagnóstico pericial, reemplazo de componentes de hardware/redes y certificación técnica de la solución.

```
┌────────────────┐      Despacho Ticket       ┌─────────────────────────────────┐
│   Operador     ├───────────────────────────►│   GIT - Plataforma Operativa    │
│     APOC       │◄───────────────────────────┤ (Folios Atómicos, Telemetría,   │
└────────────────┘      Visto Bueno Cierre    │  Gestión de SLAs, Trazabilidad) │
                                              └───────────────┬─────────────────┘
                                                              │ Asignación y
                                                              │ Bitácora Técnica
                                                              ▼
                                              ┌─────────────────────────────────┐
                                              │        Especialista GTS         │
                                              │   (Diagnóstico y Reparación)    │
                                              └─────────────────────────────────┘
```

---

## 2. ARQUITECTURA TÉCNICA

GIT implementa una arquitectura desacoplada orientada a servicios (**MVC Extendido + Service Layer + Form Requests + Policies**) que erradica controladores monolíticos y concentra las reglas aeronáuticas en servicios aislados:

```
[Cliente Web / Consola Táctica]
              │
              ▼ (HTTPS / TLS 1.3)
      [Router Laravel]
              │
              ▼
   [Middlewares de Seguridad] ──► SecurityHeaders, Auth, EnsureUserHasRole, CSRF
              │
              ▼
    [Thin Controllers]        ──► DashboardController, IncidentController, AuditController
              │
    ┌─────────┴─────────┐
    ▼                   ▼
[Form Requests]    [Policies] ──► Validación estricta y autorización RBAC por servidor
    │                   │
    └─────────┬─────────┘
              ▼
       [Service Layer]        ──► IncidentService, DashboardService, AuditService,
              │                   FolioGeneratorService, FileStorageService
              ▼
      [Eloquent Models]       ──► Incident, User, Category, Priority, Status,
              │                   IncidentHistory, IncidentAttachment
              ▼
     [PostgreSQL 16+]         ──► Transacciones ACID, bloqueo pesimista lockForUpdate()
```

---

## 3. CICLO DE VIDA Y MÁQUINA DE ESTADOS (FSM)

El ciclo de vida de una incidencia tecnológica sigue un flujo determinista gobernado por una máquina de estados finitos con salvaguardas estrictas:

```
                  ┌──────────────┐
                  │    NUEVA     │ (Despacho APOC con Folio Atómico INC-YYYY-XXXX)
                  └──────┬───────┘
                         │
                         │ [Asignación / Autoasignación GTS]
                         ▼
                  ┌──────────────┐
                  │   ASIGNADA   │ (Técnico GTS asignado con timestamp)
                  └──────┬───────┘
                         │
                         │ [Iniciar Atención en Sitio]
                         ▼
           ┌────────► ┌──────────────┐
Rechazo    │          │ EN_ATENCION  │ (Diagnóstico en curso, notas técnicas)
por APOC   │          └──────┬───────┘
           │                 │
           │                 │ [Certificación Técnica de Solución]
           │                 ▼
           │          ┌──────────────┐
           └──────────┤   RESUELTA   │ (Detalle de solución técnica por GTS)
                      └──────┬───────┘
                             │
                             │ [Visto Bueno Operacional APOC]
                             ▼
                      ┌──────────────┐
                      │   CERRADA    │ (Expediente sellado e inmutable)
                      └──────────────┘
```

### Reglas de Negocio Clave:
1. **Inmutabilidad del Folio:** Todo ticket recibe una clave única irrepetible `INC-YYYY-XXXX` generada con bloqueo transaccional concurrente (`lockForUpdate()`).
2. **Segregación de Roles:** Solo GTS puede emitir soluciones técnicas; solo APOC puede cerrar o rechazar tickets.
3. **Bloqueo Posterior al Cierre:** Una vez en estado `CERRADA`, el expediente queda protegido contra escritura (no se admiten nuevas notas, evidencias o cambios de estado).
4. **Auditoría Append-Only:** Todo cambio genera un registro inmutable en `incident_histories` que no puede ser modificado ni borrado por ningún rol.

---

## 4. SUBSISTEMAS TECNOLÓGICOS DEL AIFA CUBIERTOS

| Subsistema | Código | Descripción Técnica |
|---|:---:|---|
| **CCTV y Videovigilancia** | `cctv` | Cámaras PTZ, domos fijos, servidores VMS Genetec y matrices NVR perimetrales. |
| **Control de Acceso** | `access-control` | Torniquetes, e-Gates biométricos, esclusas y electroimanes de puertas de embarque. |
| **FIDS / Pantallas de Vuelo** | `fids` | Clústeres de cartelería digital, controladores multimedia y pantallas en SUE. |
| **Radiocomunicación TETRA** | `tetra` | Estaciones base, repetidores troncalizados y terminales portátiles de coordinación. |
| **Redes e Infraestructura IT** | `network` | Switches de acceso PoE, fibra monomodo/multimodo, racks IDF/MDF y core de datos. |
| **Kioscos CUSS** | `cuss` | Módulos de auto-documentación de pasajeros e impresoras de pases de abordar. |
| **Manejo de Equipajes BHS** | `bhs` | Sensores PLC, lectores láser de código de barras y servidores SCADA de carruseles. |

---

## 5. ROLES Y CREDENCIALES PRECONFIGURADAS

Para fines de evaluación, homologación y pruebas operativas, el seeder crea las siguientes cuentas:

| Rol Institucional | Nombre de Usuario | Correo Electrónico | Contraseña | Facultades Operativas |
|---|---|---|:---:|---|
| **Operador APOC** | Tco. M. Reyes | `apoc@aifa.aero` | `Password123!` | Reporte de fallas, carga de fotos, monitoreo de SLAs, rechazo de soluciones insatisfactorias, visto bueno y cierre definitivo. |
| **Coordinador GTS** | Ing. Roberto Morales | `gts.morales@aifa.aero` | `Password123!` | Asignación técnica de cuadrillas, toma de incidencias, supervisión de turnos, consulta de auditoría global. |
| **Especialista GTS** | Ing. Carlos Mendoza | `gts.mendoza@aifa.aero` | `Password123!` | Atención en sitio, registro de bitácora técnica de campo, certificación y documentación de resolución. |
| **Administrador** | Admin SITIA / AIFA | `admin@aifa.aero` | `Password123!` | Acceso universal de supervisión pericial, gestión de catálogos y auditoría forense. |

---

## 6. GUÍA DE INSTALACIÓN Y PUESTA EN MARCHA LOCAL

### Requisitos del Sistema:
- **PHP:** 8.2 o superior (con extensiones `pdo_pgsql`, `mbstring`, `openssl`, `curl`, `tokenizer`, `xml`, `fileinfo`).
- **Composer:** 2.x o superior.
- **Node.js:** 18.x o superior y npm.
- **PostgreSQL:** 14.x o superior en ejecución local o remota.

### Paso a Paso de Instalación:

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/tu-organizacion/git-aifa.git
   cd git-aifa
   ```

2. **Instalar dependencias de PHP y Node.js:**
   ```bash
   composer install --optimize-autoloader
   npm install
   ```

3. **Configurar el entorno (`.env`):**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configurar conexión a PostgreSQL en `.env`:**
   ```ini
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=git_incidentes
   DB_USERNAME=tu_usuario_postgres
   DB_PASSWORD=tu_password_postgres
   ```

5. **Crear la base de datos (en PostgreSQL):**
   ```sql
   CREATE DATABASE git_incidentes;
   ```

6. **Ejecutar migraciones y sembrado de catálogos/usuarios:**
   ```bash
   php artisan migrate:fresh --seed
   ```

7. **Compilar recursos de interfaz (Tailwind CSS / Vite):**
   ```bash
   npm run build
   ```

8. **Iniciar el servidor web local:**
   ```bash
   php artisan serve
   ```
   Acceder a través de: `http://127.0.0.1:8000`.

---

## 7. SUITE DE PRUEBAS AUTOMATIZADAS (QA Y SEGURIDAD)

El sistema cuenta con **149 pruebas automatizadas y 570 aserciones** que validan el 100% de la funcionalidad, seguridad y ciclo de vida de la aplicación:

```bash
php artisan test
```

### Resumen de Pruebas:
- **Autenticación y Sesión:** Validación de credenciales, bloqueo de usuarios inactivos y protección contra ataques de fuerza bruta.
- **Autorización RBAC:** Matriz de permisos cruzados entre roles APOC, GTS y Admin.
- **Máquina de Estados y Reglas de Negocio:** Invariantes de transición y generación atómica de folios continuos.
- **Trazabilidad y Auditoría:** Imposibilidad de mutar o eliminar bitácoras de auditoría (*append-only*).
- **Almacenamiento Seguro:** Prevención de subida de ejecutables (`.exe`, `.php`), límites de tamaño (10MB) y mitigación de IDOR.
- **Blindaje Web:** Inyección de cabeceras de seguridad HTTP, protección contra XSS, CSRF y SQL Injection.
- **Prueba Integral E2E:** Flujo completo continuo de despacho, asignación, intervención, rechazo, re-solución y cierre pericial.

---

## 8. GUÍA DE DESPLIEGUE EN PRODUCCIÓN (LINUX / NGINX / SYSTEMD)

Para el despliegue en servidores perimetrales o centros de datos del AIFA:

1. **Optimización de Caché:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Permisos del Sistema de Archivos:**
   ```bash
   chown -R www-data:www-data /var/www/git
   chmod -R 775 /var/www/git/storage /var/www/git/bootstrap/cache
   ```

3. **Configuración de VirtualHost en Nginx (`/etc/nginx/sites-available/git`):**
   ```nginx
   server {
       listen 80;
       server_name git.aifa.aero;
       return 301 https://$host$request_uri;
   }

   server {
       listen 443 ssl http2;
       server_name git.aifa.aero;
       root /var/www/git/public;

       ssl_certificate /etc/ssl/certs/git_aifa.crt;
       ssl_certificate_key /etc/ssl/private/git_aifa.key;

       add_header X-Frame-Options "SAMEORIGIN" always;
       add_header X-XSS-Protection "1; mode=block" always;
       add_header X-Content-Type-Options "nosniff" always;
       add_header Referrer-Policy "strict-origin-when-cross-origin" always;

       index index.php;
       charset utf-8;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

4. **Políticas de Respaldo de Base de Datos (Cron Nocturno):**
   ```bash
   0 3 * * * pg_dump -U postgres -Fc git_incidentes > /var/backups/git_db_$(date +\%Y\%m\%d).dump
   ```

---

## 9. MAPA DE DOCUMENTACIÓN DEL PROYECTO (30 PASOS)

Toda la trazabilidad metodológica de desarrollo se encuentra documentada en el directorio [`docs/`](docs/):

- **Fase 1 (Análisis y Arquitectura):** [Docs 01 a 06](docs/) (Requisitos, Arquitectura, Módulos, Roles, Flujo FSM, Modelo E/R).
- **Fase 2 (Preparación del Entorno):** [Docs 07 a 11](docs/) (Entorno, Laravel, PostgreSQL, Git, Autenticación).
- **Fase 3 (Base de Datos y Modelos):** [Docs 12 a 15](docs/) (Migraciones, Relaciones Eloquent, Seeders, Factories).
- **Fase 4 (Seguridad y Autorización):** [Docs 16 a 18](docs/) (RBAC Middleware, Policies, Form Requests).
- **Fase 5 (Módulo de Incidencias):** [Docs 19 a 25](docs/) (Creación, Listado, Asignación, Notas, Resolución, Rechazo, Cierre).
- **Fase 6 (Trazabilidad y Evidencias):** [Docs 26 a 27](docs/) (Auditoría Inmutable, Archivos Adjuntos).
- **Fase 7 (Interfaz Operativa):** [Doc 28](docs/28_dashboard_operativo.md) (Dashboard Táctico, Telemetría de Turno y MTTR).
- **Fase 8 (Aseguramiento de Calidad):** [Doc 29](docs/29_pruebas_integrales_y_seguridad.md) (Pruebas E2E, Blindaje y Seguridad).
- **Fase 9 (Cierre y Entrega):** [Doc 30](docs/30_cierre_y_manual_de_despliegue.md) (Manual de Despliegue y Cierre de Proyecto).

---

## 10. LICENCIA Y DERECHOS
Desarrollado para el **Aeropuerto Internacional Felipe Ángeles (AIFA)** y el **Centro de Operaciones Aeroportuarias (APOC / GTS)**.  
Uso interno institucional regulado por la normativa de seguridad de la información aeronáutica.