# CONFIGURACIÓN DEL SISTEMA DE AUTENTICACIÓN
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**Scaffolding:** Laravel Breeze (Blade Stack + Tailwind CSS + Dark Mode)  
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. COMPONENTES INSTALADOS

Se integró **Laravel Breeze** configurado con plantillas Blade y soporte nativo para tema oscuro (*Dark Mode*):
* **Controladores de Autenticación (`app/Http/Controllers/Auth/`):**
  * `AuthenticatedSessionController.php`: Inicio (`GET /login`) y cierre de sesión (`POST /logout`).
  * `RegisteredUserController.php`: Registro de usuarios (`POST /register`).
  * `PasswordResetLinkController.php` & `NewPasswordController.php`: Flujo de recuperación de contraseñas.
  * `ProfileController.php`: Gestión de perfil de usuario (`GET/PATCH /profile`).
* **Vistas de Presentación (`resources/views/auth/`, `resources/views/layouts/`):**
  * `login.blade.php`: Pantalla institucional de acceso.
  * `register.blade.php`: Registro de usuarios.
  * `layouts/app.blade.php`: Plantilla base autenticada.
  * `layouts/guest.blade.php`: Plantilla para pantallas públicas/login.

---

### 2. CONFIGURACIÓN DE TEMA INSTITUCIONAL EN TAILWIND

Se extendió [`tailwind.config.js`](file:///c:/Users/Asus/Desktop/GIT/tailwind.config.js) con la paleta de colores oficial del sistema **GIT**:
* `surface-base`: `#0B0F17` (Fondo general ultra-oscuro)
* `surface-card`: `#161F30` (Tarjetas y contenedores elevados)
* `surface-overlay`: `#1E293B` (Superficie interactiva)
* `primary`: `#4CD7F6` y `primary-container`: `#06B6D4` (Cyan de telecomunicaciones)
* `status-resolved`: `#10B981` (Verde esmeralda para tickets resueltos)
* `status-warning`: `#F59E0B` (Ámbar de atención)
* `status-critical`: `#EF4444` (Rojo de criticidad aeroportuaria)
* `infra-cctv`, `infra-access`, `infra-fids`, `infra-network`, `infra-radio` (Colores por subsistema)
* Fuentes: `Inter` para textos y `JetBrains Mono` para folios (`code-folio`).

---

### 3. COMPILACIÓN DE ASSETS CON VITE

Los archivos de estilos y scripts fueron empaquetados en modo producción mediante Vite:
* CSS compilado: `public/build/assets/app-*.css`
* JS compilado: `public/build/assets/app-*.js`

