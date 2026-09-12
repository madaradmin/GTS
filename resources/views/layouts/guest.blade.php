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
    <body class="font-sans text-brand-text-primary antialiased bg-brand-canvas min-h-screen flex flex-col justify-center items-center p-4 selection:bg-brand-primary selection:text-white">
        <div class="w-full sm:max-w-md flex flex-col items-center gap-6">
            <!-- Emblema Institucional GTS -->
            <a href="/" class="flex flex-col items-center gap-2 group focus:outline-none">
                <x-application-logo class="w-20 h-20" />
                <div class="flex flex-col items-center text-center mt-1">
                    <span class="text-xl font-bold text-brand-text-primary tracking-wider leading-tight">
                        GTS AIFA
                    </span>
                    <span class="text-xs text-brand-text-muted mt-0.5">
                        Grupo de Seguridad Tecnológica
                    </span>
                </div>
            </a>

            <!-- Tarjeta de Autenticación -->
            <div class="w-full bg-brand-surface border border-brand-border rounded-2xl shadow-enterprise p-6 sm:p-8">
                {{ $slot }}
            </div>

            <!-- Footer Táctico -->
            <div class="flex items-center gap-2 text-[11px] font-mono text-brand-text-muted">
                <span class="w-1.5 h-1.5 rounded-full bg-[#20C997]"></span>
                <span>NOC / APOC • Sistema Operativo Seguro</span>
            </div>
        </div>
    </body>
</html>
