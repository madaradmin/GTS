# CONFIGURACIÓN DE POSTGRESQL Y VARIABLES DE ENTORNO
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**Base de Datos:** PostgreSQL 18.6  
**Nombre de BD:** `git_incidentes`  
**Host/Puerto:** `127.0.0.1:5432`  
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. CONFIGURACIÓN DE BASE DE DATOS EN `.env`

Se configuró la conexión a PostgreSQL en `.env` y `.env.example`:

```ini
APP_NAME="GIT - Gestión de Incidencias"
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE="America/Mexico_City"
APP_URL=http://localhost:8000

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_MX

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=git_incidentes
DB_USERNAME=postgres
DB_PASSWORD=
```

---

### 2. COMANDOS DE CONTROL DE POSTGRESQL (DAEMON)

* **Iniciar PostgreSQL (en segundo plano):**
  ```powershell
  postgres -D "$env:USERPROFILE\scoop\apps\postgresql\current\data"
  ```
* **Verificar estado de conexión desde Laravel:**
  ```powershell
  php artisan db:show
  ```
* **Acceso a consola interactiva de PostgreSQL:**
  ```powershell
  psql -U postgres -d git_incidentes
  ```

---

### 3. RESULTADO DE VERIFICACIÓN CON ARTISAN

Al ejecutar `php artisan db:show`:
* **Motor:** PostgreSQL 18.6
* **Conexión:** pgsql
* **Base de Datos:** git_incidentes
* **Host:** 127.0.0.1:5432
* **Estado:** Conectado exitosamente con conexiones activas listas para migraciones.

