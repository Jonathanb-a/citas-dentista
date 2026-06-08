<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Dentissa Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FFF6F6]">

<div class="min-h-screen flex">

    {{-- Sidebar --}}
    <aside class="w-64 bg-white shadow-sm fixed top-0 left-0 h-full flex flex-col">

        {{-- Logo --}}
        <div class="flex items-center justify-center py-6 border-b border-gray-100">
            <img src="{{ asset('img/logo-dentista.png') }}"
                 alt="Logo"
                 class="h-16 w-auto object-contain">
        </div>

        {{-- Menu --}}
        <nav class="flex flex-col gap-1 px-4 py-6 flex-1">

            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[#fde7ed] text-[#d75078] font-medium text-sm transition-all duration-300"
               style="font-family: 'Montserrat', sans-serif;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <a href="#"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078] font-medium text-sm transition-all duration-300"
               style="font-family: 'Montserrat', sans-serif;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Citas
            </a>

            <a href="#"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078] font-medium text-sm transition-all duration-300"
               style="font-family: 'Montserrat', sans-serif;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Pacientes
            </a>

            <a href="#"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078] font-medium text-sm transition-all duration-300"
               style="font-family: 'Montserrat', sans-serif;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Horarios
            </a>

            <a href="#"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078] font-medium text-sm transition-all duration-300"
               style="font-family: 'Montserrat', sans-serif;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Servicios
            </a>

            <a href="#"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078] font-medium text-sm transition-all duration-300"
               style="font-family: 'Montserrat', sans-serif;">
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
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-gray-500 hover:bg-red-50 hover:text-red-500 font-medium text-sm transition-all duration-300"
                        style="font-family: 'Montserrat', sans-serif;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Cerrar sesión
                </button>
            </form>
        </div>

    </aside>

    {{-- Contenido principal --}}
    <main class="ml-64 flex-1 p-8">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="font-bold text-gray-800 text-2xl"
                    style="font-family: 'Montserrat', sans-serif;">
                    Bienvenida, {{ auth()->user()->name }} 👋
                </h1>
                <p class="font-light text-gray-400 text-sm mt-1"
                   style="font-family: 'Montserrat', sans-serif;">
                    {{ now()->format('l, d \d\e F \d\e Y') }}
                </p>
            </div>
        </div>

        {{-- Tarjetas de estadisticas --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            {{-- Citas hoy --}}
            <div class="bg-white rounded-2xl p-6 shadow-sm flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <span class="text-gray-400 text-sm font-medium" style="font-family: 'Montserrat', sans-serif;">Citas hoy</span>
                    <div class="w-10 h-10 bg-[#fde7ed] rounded-full flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#d75078]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <span class="font-bold text-gray-800 text-3xl" style="font-family: 'Montserrat', sans-serif;">{{ $citasHoy }}</span>
                <span class="text-gray-400 text-xs" style="font-family: 'Montserrat', sans-serif;">Total del día</span>
            </div>

            {{-- Pendientes --}}
            <div class="bg-white rounded-2xl p-6 shadow-sm flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <span class="text-gray-400 text-sm font-medium" style="font-family: 'Montserrat', sans-serif;">Pendientes</span>
                    <div class="w-10 h-10 bg-yellow-50 rounded-full flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <span class="font-bold text-gray-800 text-3xl" style="font-family: 'Montserrat', sans-serif;">{{ $citasPendientes }}</span>
                <span class="text-gray-400 text-xs" style="font-family: 'Montserrat', sans-serif;">Por atender</span>
            </div>

            {{-- Atendidas --}}
            <div class="bg-white rounded-2xl p-6 shadow-sm flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <span class="text-gray-400 text-sm font-medium" style="font-family: 'Montserrat', sans-serif;">Atendidas</span>
                    <div class="w-10 h-10 bg-green-50 rounded-full flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <span class="font-bold text-gray-800 text-3xl" style="font-family: 'Montserrat', sans-serif;">{{ $citasAtendidas }}</span>
                <span class="text-gray-400 text-xs" style="font-family: 'Montserrat', sans-serif;">Completadas hoy</span>
            </div>

            {{-- Canceladas --}}
            <div class="bg-white rounded-2xl p-6 shadow-sm flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <span class="text-gray-400 text-sm font-medium" style="font-family: 'Montserrat', sans-serif;">Canceladas</span>
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <span class="font-bold text-gray-800 text-3xl" style="font-family: 'Montserrat', sans-serif;">{{ $citasCanceladas }}</span>
                <span class="text-gray-400 text-xs" style="font-family: 'Montserrat', sans-serif;">Canceladas hoy</span>
            </div>

        </div>

        {{-- Citas de hoy --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-bold text-gray-800 text-lg mb-6"
                style="font-family: 'Montserrat', sans-serif;">
                Citas de hoy
            </h2>

            @if($citas->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-gray-400 text-sm" style="font-family: 'Montserrat', sans-serif;">No hay citas programadas para hoy</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium" style="font-family: 'Montserrat', sans-serif;">HORA</th>
                                <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium" style="font-family: 'Montserrat', sans-serif;">PACIENTE</th>
                                <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium" style="font-family: 'Montserrat', sans-serif;">SERVICIO</th>
                                <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium" style="font-family: 'Montserrat', sans-serif;">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($citas as $cita)
                            <tr class="border-b border-gray-50 hover:bg-[#FFF6F6] transition-colors duration-200">
                                <td class="py-4 px-4 text-gray-700 text-sm font-medium" style="font-family: 'Montserrat', sans-serif;">
                                    {{ \Carbon\Carbon::parse($cita->hora)->format('h:i A') }}
                                </td>
                                <td class="py-4 px-4" style="font-family: 'Montserrat', sans-serif;">
                                    <span class="text-gray-700 text-sm font-medium">{{ $cita->paciente->name }} {{ $cita->paciente->apellidos }}</span>
                                </td>
                                <td class="py-4 px-4 text-gray-500 text-sm" style="font-family: 'Montserrat', sans-serif;">
                                    {{ $cita->servicio->nombre }}
                                </td>
                                <td class="py-4 px-4">
                                    @if($cita->status === 'pendiente')
                                        <span class="bg-yellow-50 text-yellow-600 text-xs font-medium px-3 py-1 rounded-full" style="font-family: 'Montserrat', sans-serif;">Pendiente</span>
                                    @elseif($cita->status === 'confirmada')
                                        <span class="bg-blue-50 text-blue-600 text-xs font-medium px-3 py-1 rounded-full" style="font-family: 'Montserrat', sans-serif;">Confirmada</span>
                                    @elseif($cita->status === 'atendida')
                                        <span class="bg-green-50 text-green-600 text-xs font-medium px-3 py-1 rounded-full" style="font-family: 'Montserrat', sans-serif;">Atendida</span>
                                    @elseif($cita->status === 'cancelada')
                                        <span class="bg-red-50 text-red-600 text-xs font-medium px-3 py-1 rounded-full" style="font-family: 'Montserrat', sans-serif;">Cancelada</span>
                                    @elseif($cita->status === 'reagendada')
                                        <span class="bg-purple-50 text-purple-600 text-xs font-medium px-3 py-1 rounded-full" style="font-family: 'Montserrat', sans-serif;">Reagendada</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </main>

</div>

</body>
</html>