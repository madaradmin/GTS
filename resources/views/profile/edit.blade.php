<x-app-layout>
    <div class="w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col gap-6">
        
        <!-- Encabezado de Perfil Operativo -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-brand-border">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs uppercase tracking-widest text-blue-400 font-semibold">Credenciales y Seguridad</span>
                    <span class="text-brand-text-faint">•</span>
                    <span class="text-xs uppercase tracking-widest text-brand-text-muted font-mono">AIFA ID-{{ auth()->id() }}</span>
                </div>
                <h1 class="text-2xl font-bold text-brand-text-primary tracking-tight">Perfil de Operador</h1>
                <p class="text-xs text-brand-text-muted mt-0.5">Gestión de identidad, contraseña y parámetros de cuenta institucional AIFA.</p>
            </div>
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-brand-surface border border-brand-border text-xs font-mono text-brand-text-secondary self-start sm:self-auto">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Sesión Activa</span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- Información del Perfil -->
            <div class="p-6 bg-brand-surface border border-brand-border rounded-xl shadow-enterprise">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- Actualización de Contraseña -->
            <div class="p-6 bg-brand-surface border border-brand-border rounded-xl shadow-enterprise">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- Zona de Riesgo / Baja de Cuenta -->
            <div class="p-6 bg-brand-surface border border-rose-500/20 rounded-xl shadow-enterprise bg-rose-500/[0.02]">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
