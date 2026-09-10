# PREPARACIÓN DEL ENTORNO DE DESARROLLO
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**Entorno Operativo:** Windows 11 / PowerShell  
**Gestor de Paquetes:** Scoop (User-Level Portable Toolchain)  
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. HERRAMIENTAS Y VERSIONES INSTALADAS

| Componente | Versión Verificada | Ubicación en Disco | Propósito en el Proyecto |
|---|---|---|---|
| **Git** | 2.55.0 | `~\scoop\apps\git\current\cmd` | Control de versiones y trazabilidad de commits. |
| **PHP** | 8.5.10 (CLI ZTS) | `~\scoop\apps\php\current` | Intérprete y motor de ejecución del backend. |
| **Composer** | 2.10.3 | `~\scoop\apps\composer\current` | Gestor de dependencias y paquetes PHP (Laravel). |
| **PostgreSQL** | 18.6 | `~\scoop\apps\postgresql\current\bin` | Servidor de base de datos relacional ACID. |
| **Node.js** | 24.21.0 (LTS) | `~\scoop\apps\nodejs-lts\current` | Runtime para compilación de assets con Vite. |
| **NPM** | 11.19.0 | `~\scoop\apps\nodejs-lts\current` | Gestor de dependencias frontend (Tailwind CSS). |

---

### 2. CONFIGURACIÓN DE PHP (`php.ini`)

El archivo `php.ini` fue configurado en `C:\Users\Asus\scoop\apps\php\current\php.ini` con las extensiones indispensables para el funcionamiento del framework Laravel y PostgreSQL:

```ini
extension_dir = "ext"

; Extensiones habilitadas para GIT
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_pgsql
extension=pgsql
extension=pdo_sqlite
extension=sqlite3
extension=zip
```

---

### 3. VARIABLES DE ENTORNO DEL USUARIO (`PATH`)

Para garantizar la disponibilidad de los comandos en cualquier terminal nueva de PowerShell, se actualizaron las variables de entorno a nivel de usuario (`[Environment]::SetEnvironmentVariable("Path", ..., "User")`):
* `C:\Users\Asus\scoop\shims`
* `C:\Users\Asus\scoop\apps\nodejs-lts\current`
* `C:\Users\Asus\scoop\apps\postgresql\current\bin`

---

### 4. GESTIÓN DEL SERVICIO POSTGRESQL

Para iniciar o detener el servidor de base de datos localmente durante el desarrollo:
* **Iniciar base de datos:** `pg_ctl -D "$env:USERPROFILE\scoop\apps\postgresql\current\data" -l "$env:USERPROFILE\scoop\apps\postgresql\current\server.log" start`
* **Detener base de datos:** `pg_ctl -D "$env:USERPROFILE\scoop\apps\postgresql\current\data" stop`
* **Usuario por defecto:** `postgres` (conexión local de confianza).

