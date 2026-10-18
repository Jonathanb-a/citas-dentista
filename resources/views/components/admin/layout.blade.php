<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Admin' }} — Dentissa</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FFF6F6]" style="font-family: 'Montserrat', sans-serif;">

<div class="min-h-screen flex" x-data="{ sidebarOpen: false }">

    {{-- Sidebar --}}
    <aside class="w-64 bg-white shadow-sm fixed top-0 left-0 h-full flex flex-col z-40 transition-transform duration-300"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">

        {{-- Logo --}}
        <div class="flex items-center justify-center py-6 border-b border-gray-100">
            <img src="{{ asset('img/logo-dentista.png') }}" alt="Logo" class="h-16 w-auto object-contain">
        </div>

        {{-- Menu --}}
        <nav class="flex flex-col gap-1 px-4 py-6 flex-1 overflow-y-auto">

            {{-- Dashboard --}}
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 {{ request()->routeIs('admin.dashboard') ? 'bg-[#fde7ed] text-[#d75078]' : 'text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078]' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            {{-- Citas --}}
         <a href="{{ route('admin.citas.index') }}"
   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 {{ request()->routeIs('admin.citas.*') ? 'bg-[#fde7ed] text-[#d75078]' : 'text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078]' }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
    Citas
</a>

            {{-- Pacientes --}}
            <a href="{{ route('admin.pacientes.index') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 {{ request()->routeIs('admin.pacientes.*') ? 'bg-[#fde7ed] text-[#d75078]' : 'text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078]' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Pacientes
            </a>

            {{-- Personal --}}
            <a href="{{ route('admin.personal.index') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 {{ request()->routeIs('admin.personal.*') ? 'bg-[#fde7ed] text-[#d75078]' : 'text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078]' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Personal
            </a>

            {{-- Horarios --}}
<a href="{{ route('admin.horarios.index') }}"
   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 {{ request()->routeIs('admin.horarios.*') ? 'bg-[#fde7ed] text-[#d75078]' : 'text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078]' }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    Horarios
</a>
            {{-- Bloqueos --}}
            <a href="{{ route('admin.bloqueos.index') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 {{ request()->routeIs('admin.bloqueos.*') ? 'bg-[#fde7ed] text-[#d75078]' : 'text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078]' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                Bloqueos
            </a>

            {{-- Especialidades --}}
            <a href="{{ route('admin.especialidades.index') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 {{ request()->routeIs('admin.especialidades.*') ? 'bg-[#fde7ed] text-[#d75078]' : 'text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078]' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Especialidades
            </a>

            {{-- Servicios --}}
            <a href="{{ route('admin.servicios.index') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-300 {{ request()->routeIs('admin.servicios.*') ? 'bg-[#fde7ed] text-[#d75078]' : 'text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078]' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Servicios
            </a>

            {{-- Landing Page --}}
            <a href="#"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078] transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Landing Page
            </a>

        </nav>

        {{-- Logout --}}
        <div class="px-4 py-6 border-t border-gray-100">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-gray-500 hover:bg-red-50 hover:text-red-500 transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Cerrar sesión
                </button>
            </form>
        </div>

    </aside>

    {{-- Overlay mobile --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak
         class="fixed inset-0 bg-black/30 z-30 md:hidden"></div>

    {{-- Contenido principal --}}
    <main class="flex-1 md:ml-64 p-4 md:p-8 w-full min-w-0 overflow-x-hidden">

        {{-- Boton menu mobile --}}
        <button @click="sidebarOpen = true" class="md:hidden mb-4 p-2 bg-white rounded-lg shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Contraseña temporal --}}
        @include('partials.password-temporal')

        {{-- Mensaje de exito --}}
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm flex items-center justify-between">
                <span>{{ session('success') }}</span>
                <button @click="show = false" class="text-green-700">✕</button>
            </div>
        @endif

        {{-- Mensaje de error --}}
        @if(session('error'))
            <div x-data="{ show: true }" x-show="show"
                 class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm flex items-center justify-between">
                <span>{{ session('error') }}</span>
                <button @click="show = false" class="text-red-700">✕</button>
            </div>
        @endif

        {{-- Contenido de cada pagina --}}
        {{ $slot }}

    </main>

</div>

</body>
</html>