# CONFIGURACIÓN DE CONTROL DE VERSIONES (GIT)
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**Repositorio Remoto Oficial:** `https://github.com/madaradmin/GIT.git`  
**Rama Principal:** `main`  
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. ESTADO DEL REPOSITORIO LOCAL

El repositorio Git local ha sido inicializado en `c:\Users\Asus\Desktop\GIT` con las carpetas arquitectónicas creadas y el primer commit base:

```powershell
git status
# On branch main
# nothing to commit, working tree clean
```

* **Remote Origin:** `https://github.com/madaradmin/GIT.git`
* **Primer Commit Base:** `514ea78` — `chore: initial project structure, architecture docs and laravel skeleton`

---

### 2. AUDITORÍA DE SEGURIDAD EN `.gitignore`

Se verificó que los siguientes elementos sensibles e instalados por dependencias permanezcan fuera del repositorio:
* `.env` (credenciales y secretos locales).
* `vendor/` (código de terceros descargado por Composer).
* `node_modules/` (dependencias frontend de NPM).
* `storage/*.key` (claves de cifrado de OAuth/Passport o sesiones).
* `*.log` (bitácoras de errores de Laravel o PostgreSQL).

---

### 3. ESTÁNDAR DE COMMITS (CONVENTIONAL COMMITS)

Para mantener un historial pericial limpio y comprensible, cada paso de trabajo registrará commits con la siguiente taxonomía:
* `feat:` Nueva funcionalidad o módulo (e.g. `feat: implement incident creation and folio generator`).
* `fix:` Corrección de fallos o validaciones (e.g. `fix: validate transition from resolved to closed`).
* `refactor:` Optimización o reestructuración de código sin alterar el comportamiento.
* `test:` Creación de pruebas unitarias o de integración (`test: add feature tests for incident policy`).
* `chore:` Ajustes de configuración, migraciones base o documentación técnica.

