<x-paciente.layout title="Inicio">

    {{-- Saludo --}}
    <div class="mb-8">
        <h1 class="font-bold text-gray-800 text-2xl">Hola, {{ auth()->user()->name }} 👋</h1>
        <p class="font-light text-gray-400 text-sm mt-1">Bienvenida a tu portal de citas</p>
    </div>

        {{-- Aviso de correo sin verificar --}}
    @if(!auth()->user()->hasVerifiedEmail())
        <div class="mb-8 bg-yellow-50 border border-yellow-200 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-yellow-800 text-sm font-medium">Verifica tu correo electrónico</p>
                <p class="text-yellow-700 text-xs mt-0.5">Te enviamos un enlace a <strong>{{ auth()->user()->email }}</strong>. Verificarlo te permitirá recuperar tu contraseña si la olvidas.</p>
            </div>
            <form method="POST" action="{{ route('verification.send') }}" class="flex-shrink-0">
                @csrf
                <button type="submit"
                        class="w-full sm:w-auto bg-yellow-500 text-white text-xs font-medium px-4 py-2.5 rounded-xl hover:bg-yellow-600 transition-all duration-300">
                    Reenviar correo
                </button>
            </form>
        </div>
    @endif
    s
    {{-- Próxima cita destacada --}}
    @if($proximaCita)
        <div class="bg-[#d75078] rounded-2xl shadow-sm p-8 mb-8 text-white">
            <div class="flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-sm font-medium opacity-90">Tu próxima cita</span>
            </div>
            <h2 class="font-bold text-2xl mb-1">{{ $proximaCita->servicio->nombre }}</h2>
            <p class="text-sm opacity-80 mb-6">{{ $proximaCita->servicio->especialidad->nombre }}</p>
            <div class="flex flex-wrap items-center gap-x-8 gap-y-3">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-sm">{{ \Carbon\Carbon::parse($proximaCita->fecha)->translatedFormat('d \d\e F, Y') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm">{{ \Carbon\Carbon::parse($proximaCita->hora)->format('h:i A') }}</span>
                </div>
                @if($proximaCita->status === 'pendiente')
                    <span class="bg-white/25 text-xs font-medium px-3 py-1.5 rounded-full">Pendiente de confirmación</span>
                @elseif($proximaCita->status === 'confirmada')
                    <span class="bg-white/25 text-xs font-medium px-3 py-1.5 rounded-full">Confirmada</span>
                @endif
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm p-8 mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-bold text-gray-800 text-lg">No tienes citas próximas</h2>
                <p class="text-gray-400 text-sm mt-1">Agenda tu próxima visita cuando quieras</p>
            </div>
            <a href="{{ route('paciente.citas.create') }}"
               class="bg-[#d75078] text-white text-sm font-medium px-5 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300 text-center">
                Agendar cita
            </a>
        </div>
    @endif

    {{-- Resumen --}}
    <h3 class="font-semibold text-gray-700 text-sm mb-4">Resumen de tus citas</h3>
    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-2xl shadow-sm p-5 flex flex-col items-center text-center gap-2">
            <div class="w-12 h-12 bg-yellow-50 rounded-full flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="font-bold text-gray-800 text-2xl">{{ $totalPendientes }}</span>
            <span class="text-gray-400 text-xs font-medium">Pendientes</span>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-5 flex flex-col items-center text-center gap-2">
            <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="font-bold text-gray-800 text-2xl">{{ $totalConfirmadas }}</span>
            <span class="text-gray-400 text-xs font-medium">Confirmadas</span>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-5 flex flex-col items-center text-center gap-2">
            <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <span class="font-bold text-gray-800 text-2xl">{{ $totalAtendidas }}</span>
            <span class="text-gray-400 text-xs font-medium">Atendidas</span>
        </div>
    </div>

    {{-- Notas de la doctora --}}
    @if($expediente && $expediente->notas_doctora)
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center gap-2 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#d75078]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h2 class="font-bold text-gray-800 text-lg">Notas de tu doctora</h2>
            </div>
            <p class="text-gray-600 text-sm leading-relaxed">{{ $expediente->notas_doctora }}</p>
        </div>
    @endif

</x-paciente.layout>