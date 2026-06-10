<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Mi cuenta' }} — Dentissa</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FFF6F6]" style="font-family: 'Montserrat', sans-serif;">

<div class="min-h-screen flex flex-col">

    {{-- Navbar superior --}}
    <nav class="w-full bg-white shadow-sm sticky top-0 z-40" x-data="{ menuAbierto: false }">
        <div class="max-w-6xl mx-auto px-6 flex items-center justify-between h-20">

            {{-- Logo --}}
            <a href="{{ route('paciente.citas.index') }}">
                <img src="{{ asset('img/logo-dentista.png') }}" alt="Logo" class="h-14 w-auto object-contain">
            </a>

            {{-- Menu desktop --}}
            <div class="hidden md:flex items-center gap-8">
                <a href="{{ route('paciente.citas.index') }}"
                   class="text-sm font-medium transition-colors duration-300 {{ request()->routeIs('paciente.citas.index') ? 'text-[#d75078]' : 'text-gray-500 hover:text-[#d75078]' }}">
                    Mis citas
                </a>
                <a href="{{ route('paciente.citas.create') }}"
                   class="text-sm font-medium transition-colors duration-300 {{ request()->routeIs('paciente.citas.create') ? 'text-[#d75078]' : 'text-gray-500 hover:text-[#d75078]' }}">
                    Agendar cita
                </a>
            </div>

            {{-- Usuario + Logout --}}
            <div class="hidden md:flex items-center gap-4">
                <span class="text-sm text-gray-600">Hola, {{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="text-sm font-medium text-gray-500 hover:text-red-500 transition-colors duration-300">
                        Salir
                    </button>
                </form>
            </div>

            {{-- Menu hamburguesa mobile --}}
            <button @click="menuAbierto = !menuAbierto" class="md:hidden p-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

        </div>

        {{-- Menu mobile --}}
        <div x-show="menuAbierto" x-cloak class="md:hidden bg-white border-t border-gray-100 px-6 py-4 flex flex-col gap-3">
            <a href="{{ route('paciente.citas.index') }}" class="text-sm font-medium text-gray-600">Mis citas</a>
            <a href="{{ route('paciente.citas.create') }}" class="text-sm font-medium text-gray-600">Agendar cita</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm font-medium text-red-500">Cerrar sesión</button>
            </form>
        </div>
    </nav>

    {{-- Contenido --}}
    <main class="flex-1 max-w-6xl mx-auto w-full px-6 py-8">

        {{-- Mensajes --}}
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm flex items-center justify-between">
                <span>{{ session('success') }}</span>
                <button @click="show = false">✕</button>
            </div>
        @endif

        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm flex items-center justify-between">
                <span>{{ session('error') }}</span>
                <button @click="show = false">✕</button>
            </div>
        @endif

        {{ $slot }}

    </main>

</div>

</body>
</html>