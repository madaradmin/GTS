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
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
        <!-- Google Material Symbols Outlined -->
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-brand-canvas text-brand-text-primary antialiased font-sans overflow-hidden selection:bg-brand-primary selection:text-white">
        <div 
            x-data="{ 
                sidebarHover: false,
                hoverTimeout: null,
                openSidebar() {
                    clearTimeout(this.hoverTimeout);
                    this.sidebarHover = true;
                },
                closeSidebar() {
                    clearTimeout(this.hoverTimeout);
                    this.hoverTimeout = setTimeout(() => {
                        this.sidebarHover = false;
                    }, 180);
                }
            }" 
            @keydown.escape.window="sidebarHover = false;"
            class="flex h-screen w-screen flex-col overflow-hidden bg-brand-canvas"
        >
            
            <!-- 1. Barra Superior Completa (GTS + Logotipo Oficial AIFA + Buscador + Usuario) -->
            <x-topbar />

            <!-- 2. Contenedor de Trabajo: Sidebar Vertical Izquierdo + Área de Mando Principal -->
            <div class="flex-1 flex overflow-hidden min-h-0">
                
                <!-- Sidebar Vertical Izquierdo -->
                <x-sidebar />

                <!-- Área de Contenido Principal -->
                <main class="flex-1 overflow-y-auto overflow-x-hidden relative scrollbar-thin">
                    {{ $slot }}
                </main>

            </div>

        </div>
    </body>
</html>
