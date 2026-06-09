<x-admin.layout title="Dashboard">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="font-bold text-gray-800 text-2xl">
                Bienvenida, {{ auth()->user()->name }} 👋
            </h1>
            <p class="font-light text-gray-400 text-sm mt-1">
                {{ now()->format('l, d \d\e F \d\e Y') }}
            </p>
        </div>
    </div>

    {{-- Tarjetas de estadisticas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

        <div class="bg-white rounded-2xl p-6 shadow-sm flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <span class="text-gray-400 text-sm font-medium">Citas hoy</span>
                <div class="w-10 h-10 bg-[#fde7ed] rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#d75078]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <span class="font-bold text-gray-800 text-3xl">{{ $citasHoy }}</span>
            <span class="text-gray-400 text-xs">Total del día</span>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <span class="text-gray-400 text-sm font-medium">Pendientes</span>
                <div class="w-10 h-10 bg-yellow-50 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <span class="font-bold text-gray-800 text-3xl">{{ $citasPendientes }}</span>
            <span class="text-gray-400 text-xs">Por atender</span>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <span class="text-gray-400 text-sm font-medium">Atendidas</span>
                <div class="w-10 h-10 bg-green-50 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <span class="font-bold text-gray-800 text-3xl">{{ $citasAtendidas }}</span>
            <span class="text-gray-400 text-xs">Completadas hoy</span>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <span class="text-gray-400 text-sm font-medium">Canceladas</span>
                <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <span class="font-bold text-gray-800 text-3xl">{{ $citasCanceladas }}</span>
            <span class="text-gray-400 text-xs">Canceladas hoy</span>
        </div>

    </div>

    {{-- Citas de hoy --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h2 class="font-bold text-gray-800 text-lg mb-6">Citas de hoy</h2>

        @if($citas->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-400 text-sm">No hay citas programadas para hoy</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">HORA</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">PACIENTE</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">SERVICIO</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($citas as $cita)
                        <tr class="border-b border-gray-50 hover:bg-[#FFF6F6] transition-colors duration-200">
                            <td class="py-4 px-4 text-gray-700 text-sm font-medium">
                                {{ \Carbon\Carbon::parse($cita->hora)->format('h:i A') }}
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-gray-700 text-sm font-medium">{{ $cita->paciente->name }} {{ $cita->paciente->apellidos }}</span>
                            </td>
                            <td class="py-4 px-4 text-gray-500 text-sm">{{ $cita->servicio->nombre }}</td>
                            <td class="py-4 px-4">
                                @if($cita->status === 'pendiente')
                                    <span class="bg-yellow-50 text-yellow-600 text-xs font-medium px-3 py-1 rounded-full">Pendiente</span>
                                @elseif($cita->status === 'confirmada')
                                    <span class="bg-blue-50 text-blue-600 text-xs font-medium px-3 py-1 rounded-full">Confirmada</span>
                                @elseif($cita->status === 'atendida')
                                    <span class="bg-green-50 text-green-600 text-xs font-medium px-3 py-1 rounded-full">Atendida</span>
                                @elseif($cita->status === 'cancelada')
                                    <span class="bg-red-50 text-red-600 text-xs font-medium px-3 py-1 rounded-full">Cancelada</span>
                                @elseif($cita->status === 'reagendada')
                                    <span class="bg-purple-50 text-purple-600 text-xs font-medium px-3 py-1 rounded-full">Reagendada</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</x-admin.layout>