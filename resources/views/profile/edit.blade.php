<x-app-layout>
    <div class="w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col gap-6">
        
        @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['users.view', 'roles.view']))
            <!-- Pestañas de Navegación de Configuración -->
            <div class="flex items-center gap-2 border-b border-[#263241] pb-1">
                <a 
                    href="{{ route('profile.edit') }}" 
                    class="px-4 py-2 text-xs font-semibold rounded-t-xl bg-[#11151B] border-t border-x border-[#263241] text-[#3D82C6]"
                >
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[16px]">account_circle</span>
                        <span>Mi Cuenta</span>
                    </span>
                </a>
                @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('users.view'))
                    <a 
                        href="{{ route('users.index') }}" 
                        class="px-4 py-2 text-xs font-semibold rounded-t-xl transition-colors text-[#8D98A8] hover:text-[#F2F5F8] hover:bg-[#101923]"
                    >
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">group</span>
                            <span>Gestión de Usuarios y Cuadrillas</span>
                        </span>
                    </a>
                @endif
                @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('roles.view'))
                    <a 
                        href="{{ route('roles.index') }}" 
                        class="px-4 py-2 text-xs font-semibold rounded-t-xl transition-colors text-[#8D98A8] hover:text-[#F2F5F8] hover:bg-[#101923]"
                    >
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">admin_panel_settings</span>
                            <span>Gestión de Roles y Permisos</span>
                        </span>
                    </a>
                @endif
            </div>
        @endif

        <!-- Encabezado de Perfil Operativo -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-brand-border">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs uppercase tracking-widest text-[#3D82C6] font-semibold">Credenciales y Seguridad</span>
                    <span class="text-brand-text-faint">•</span>
                    <span class="text-xs uppercase tracking-widest text-brand-text-muted font-mono">AIFA ID-{{ auth()->id() }}</span>
                </div>
                <h1 class="text-2xl font-bold text-brand-text-primary tracking-tight">Perfil de Operador</h1>
                <p class="text-xs text-brand-text-muted mt-0.5">Gestión de identidad, contraseña y parámetros de cuenta institucional AIFA.</p>
            </div>
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-brand-surface border border-brand-border text-xs font-mono text-brand-text-secondary self-start sm:self-auto">
                <span class="w-2 h-2 rounded-full bg-[#20C997] animate-pulse"></span>
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

            @if(auth()->user()->isAdmin())
                <!-- Zona de Riesgo / Baja de Cuenta -->
                <div class="p-6 bg-brand-surface border border-[#D83B52]/20 rounded-xl shadow-enterprise bg-[#D83B52]/[0.02]">
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
