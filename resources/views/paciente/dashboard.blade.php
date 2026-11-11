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
    
    {{-- Tu próxima cita --}}
    @if($proximaCita)
        @php
            $porAceptar = $proximaCita->status === 'pendiente';
            $porConfirmar = $proximaCita->status === 'confirmada' && !$proximaCita->confirmado_por_paciente;
            $yaConfirmada = $proximaCita->status === 'confirmada' && $proximaCita->confirmado_por_paciente;
        @endphp
        <div class="bg-[#c03a63] rounded-2xl shadow-sm p-6 md:p-8 mb-8 text-white" aria-labelledby="proxima-cita">
            <div class="flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span id="proxima-cita" class="text-sm font-medium">Tu próxima cita</span>
            </div>
            <h2 class="font-bold text-2xl mb-1">{{ $proximaCita->servicio->nombre }}</h2>
            <p class="text-sm opacity-90 mb-6">{{ $proximaCita->servicio->especialidad->nombre }}</p>
            <div class="flex flex-wrap items-center gap-x-8 gap-y-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm">{{ \Carbon\Carbon::parse($proximaCita->fecha)->locale('es')->translatedFormat('l d \d\e F, Y') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm">{{ \Carbon\Carbon::parse($proximaCita->hora)->format('h:i A') }}</span>
                </div>
                <span class="bg-white/25 text-xs font-medium px-3 py-1.5 rounded-full">
                    @if($porAceptar) Pendiente: la clínica aún no la acepta
                    @elseif($porConfirmar) Aceptada por la clínica
                    @else Confirmada ✓ @endif
                </span>
            </div>

            @if($porConfirmar)
                <div class="mt-6 flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl bg-white/15 p-4">
                    <p class="text-sm flex-1">La clínica aceptó tu cita. Confirma tu asistencia para reservar tu lugar.</p>
                    <form method="POST" action="{{ route('paciente.citas.confirmar', $proximaCita->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="min-h-11 w-full sm:w-auto bg-white text-[#a13153] text-sm font-semibold px-5 py-2.5 rounded-xl hover:bg-[#fde7ed] transition-all duration-300">
                            ✓ Confirmar mi asistencia
                        </button>
                    </form>
                </div>
            @elseif($porAceptar)
                <p class="mt-6 rounded-xl bg-white/15 p-4 text-sm">Estamos revisando tu cita. Cuando la clínica la acepte podrás confirmar tu asistencia aquí.</p>
            @elseif($yaConfirmada)
                <p class="mt-6 rounded-xl bg-white/15 p-4 text-sm">Ya confirmaste tu asistencia. ¡Te esperamos!</p>
            @endif

            <a href="{{ route('paciente.citas.index') }}" class="mt-5 inline-flex min-h-11 items-center text-sm font-medium underline underline-offset-4 hover:opacity-90">Ver mis citas</a>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm p-6 md:p-8 mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-bold text-gray-800 text-lg">Aún no tienes una próxima cita</h2>
                <p class="text-gray-500 text-sm mt-1">Cuando quieras, agenda tu siguiente visita en unos pasos.</p>
            </div>
            <a href="{{ route('paciente.citas.create') }}"
               class="min-h-11 bg-[#c03a63] text-white text-sm font-medium px-5 py-3 rounded-xl hover:bg-[#a13153] transition-all duration-300 text-center">
                Agendar cita
            </a>
        </div>
    @endif

    {{-- Resumen --}}
    <h3 class="font-semibold text-gray-700 text-sm mb-4">Resumen de tus citas</h3>
    <div class="grid grid-cols-3 gap-3 sm:gap-4 mb-8">
        @foreach([
            ['Total', $totalCitas, 'bg-[#fde7ed]', 'text-[#c03a63]', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['Atendidas', $totalAtendidas, 'bg-green-50', 'text-green-600', 'M5 13l4 4L19 7'],
            ['Canceladas', $totalCanceladas, 'bg-gray-100', 'text-gray-500', 'M6 18L18 6M6 6l12 12'],
        ] as [$titulo, $cantidad, $fondo, $color, $icono])
            <div class="bg-white rounded-2xl shadow-sm p-4 sm:p-5 flex flex-col items-center text-center gap-2">
                <div class="w-12 h-12 {{ $fondo }} rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 {{ $color }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icono }}"/>
                    </svg>
                </div>
                <span class="font-bold text-gray-800 text-2xl">{{ $cantidad }}</span>
                <span class="text-gray-600 text-xs font-medium">{{ $titulo }}</span>
            </div>
        @endforeach
    </div>

</x-paciente.layout>