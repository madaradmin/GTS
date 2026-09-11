<!DOCTYPE html>
<html class="dark" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'GTS - Grupo de Seguridad Tecnológica AIFA') }}</title>

        <!-- Google Fonts: Inter & JetBrains Mono -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-brand-canvas text-brand-text-primary antialiased min-h-screen flex flex-col justify-between selection:bg-brand-primary selection:text-white font-sans">
        
        <!-- Header Superior -->
        <header class="h-16 px-6 lg:px-12 border-b border-brand-border bg-brand-topbar/80 backdrop-blur flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-brand-primary-muted/40 border border-[#164A8A]/40 flex items-center justify-center shadow-enterprise-sm">
                    <x-application-logo class="w-5 h-5 text-[#3D82C6]" />
                </div>
                <div class="flex flex-col">
                    <span class="font-bold text-sm text-brand-text-primary tracking-wider leading-none">
                        GTS AIFA
                    </span>
                    <span class="text-[10px] text-brand-text-muted font-medium mt-0.5">
                        Grupo de Seguridad Tecnológica • NOC / APOC
                    </span>
                </div>
            </div>

            <nav class="flex items-center gap-3">
                @auth
                    <a
                        href="{{ route('dashboard') }}"
                        class="px-4 py-2 rounded-lg bg-brand-primary hover:bg-brand-primary-hover text-white text-xs font-bold shadow-enterprise-sm transition-all flex items-center gap-2"
                    >
                        <span class="material-symbols-outlined text-[16px]">dashboard</span>
                        <span>Ir al Dashboard</span>
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="px-4 py-2 rounded-lg bg-brand-primary hover:bg-brand-primary-hover text-white text-xs font-bold shadow-enterprise-sm transition-all flex items-center gap-2"
                    >
                        <span class="material-symbols-outlined text-[16px]">login</span>
                        <span>Acceso al Sistema</span>
                    </a>
                @endauth
            </nav>
        </header>

        <!-- Contenido Hero Principal -->
        <main class="flex-1 flex items-center justify-center p-6 lg:p-12">
            <div class="max-w-4xl w-full flex flex-col items-center text-center gap-8">
                
                <!-- Badge Superior -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-surface border border-brand-border text-xs text-brand-text-secondary shadow-enterprise-sm">
                    <span class="w-2 h-2 rounded-full bg-[#20C997] animate-pulse"></span>
                    <span class="font-mono text-[11px]">SISTEMA EN LÍNEA • OPERACIÓN CRÍTICA AIFA</span>
                </div>

                <!-- Título y Subtítulo -->
                <div class="flex flex-col gap-3 max-w-2xl">
                    <h1 class="text-3xl sm:text-5xl font-bold text-brand-text-primary tracking-tight">
                        Grupo de Seguridad Tecnológica
                    </h1>
                    <p class="text-sm sm:text-base text-brand-text-secondary leading-relaxed">
                        Plataforma institucional para el despacho, trazabilidad forense y atención inmediata de contingencias en infraestructura tecnológica del Aeropuerto Internacional Felipe Ángeles.
                    </p>
                </div>

                <!-- 3 Pilares Tecnológicos -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 w-full text-left mt-2">
                    <div class="p-4 bg-brand-surface border border-brand-border rounded-xl shadow-enterprise flex flex-col gap-2">
                        <span class="material-symbols-outlined text-[#3D82C6] text-[24px]">terminal</span>
                        <h2 class="text-xs font-bold text-brand-text-primary uppercase tracking-wider">APOC & GTS</h2>
                        <p class="text-[11px] text-brand-text-muted leading-relaxed">Coordinación en tiempo real entre el centro de mando APOC y las cuadrillas de campo GTS.</p>
                    </div>

                    <div class="p-4 bg-brand-surface border border-brand-border rounded-xl shadow-enterprise flex flex-col gap-2">
                        <span class="material-symbols-outlined text-[#E0A21A] text-[24px]">timer</span>
                        <h2 class="text-xs font-bold text-brand-text-primary uppercase tracking-wider">Control de SLA</h2>
                        <p class="text-[11px] text-brand-text-muted leading-relaxed">Métricas de MTTR calculadas positivamente, colas de atención inmediata y semáforos periciales.</p>
                    </div>

                    <div class="p-4 bg-brand-surface border border-brand-border rounded-xl shadow-enterprise flex flex-col gap-2">
                        <span class="material-symbols-outlined text-[#20C997] text-[24px]">verified_user</span>
                        <h2 class="text-xs font-bold text-brand-text-primary uppercase tracking-wider">Auditoría OACI</h2>
                        <p class="text-[11px] text-brand-text-muted leading-relaxed">Bitácora inmutable en tiempo real con evidencia técnica documental y dictamen pericial.</p>
                    </div>
                </div>

                <!-- Botón de Entrada -->
                <div class="flex items-center gap-4 mt-2">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            class="px-6 py-3 rounded-xl bg-brand-primary hover:bg-brand-primary-hover text-white text-sm font-bold shadow-enterprise transition-all flex items-center gap-2"
                        >
                            <span>Ingresar al Centro de Control</span>
                            <span class="text-[16px]">→</span>
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="px-6 py-3 rounded-xl bg-brand-primary hover:bg-brand-primary-hover text-white text-sm font-bold shadow-enterprise transition-all flex items-center gap-2"
                        >
                            <span>Ingresar con Credenciales AIFA</span>
                            <span class="text-[16px]">→</span>
                        </a>
                    @endauth
                </div>

            </div>
        </main>

        <!-- Footer Institucional -->
        <footer class="py-4 px-6 lg:px-12 border-t border-brand-border bg-brand-topbar/40 text-center text-xs text-brand-text-muted font-mono flex flex-col sm:flex-row items-center justify-between gap-2">
            <span>© {{ date('Y') }} AIFA — Aeropuerto Internacional Felipe Ángeles. Todos los derechos reservados.</span>
            <span class="text-[10px]">GTS v2.4 Enterprise Dark • Seguridad Operacional AFAC / OACI</span>
        </footer>

    </body>
</html>
