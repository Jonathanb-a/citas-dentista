<x-paciente.layout title="Mis citas">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="font-bold text-gray-800 text-2xl">Mis citas</h1>
            <p class="font-light text-gray-400 text-sm mt-1">Revisa y gestiona tus citas agendadas</p>
        </div>
        <a href="{{ route('paciente.citas.create') }}"
           class="bg-[#d75078] text-white text-sm font-medium px-5 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Agendar cita
        </a>
    </div>

    {{-- Lista de citas --}}
    @if($citas->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm flex flex-col items-center justify-center py-16 gap-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-gray-400 text-sm">No tienes citas agendadas todavía</p>
            <a href="{{ route('paciente.citas.create') }}"
               class="bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                Agendar mi primera cita
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($citas as $cita)
            <div class="bg-white rounded-2xl shadow-sm p-6 flex flex-col gap-4">

                {{-- Status --}}
                <div class="flex items-center justify-between">
                    @if($cita->status === 'pendiente')
                        <span class="bg-yellow-50 text-yellow-600 text-xs font-medium px-3 py-1 rounded-full">Pendiente de confirmación</span>
                    @elseif($cita->status === 'confirmada')
                        <span class="bg-blue-50 text-blue-600 text-xs font-medium px-3 py-1 rounded-full">Confirmada</span>
                    @elseif($cita->status === 'atendida')
                        <span class="bg-green-50 text-green-600 text-xs font-medium px-3 py-1 rounded-full">Atendida</span>
                    @elseif($cita->status === 'cancelada')
                        <span class="bg-red-50 text-red-600 text-xs font-medium px-3 py-1 rounded-full">Cancelada</span>
                    @elseif($cita->status === 'reagendada')
                        <span class="bg-purple-50 text-purple-600 text-xs font-medium px-3 py-1 rounded-full">Reagendada</span>
                    @endif
                </div>

                {{-- Servicio --}}
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">{{ $cita->servicio->nombre }}</h3>
                    <p class="text-gray-400 text-sm">{{ $cita->servicio->especialidad->nombre }}</p>
                </div>

                {{-- Fecha y hora --}}
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#d75078]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-gray-600 text-sm">{{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#d75078]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-gray-600 text-sm">{{ \Carbon\Carbon::parse($cita->hora)->format('h:i A') }}</span>
                    </div>
                </div>

              {{-- Confirmar asistencia (solo si está confirmada por la clínica y el paciente aún no confirma) --}}
@if($cita->status === 'confirmada' && !$cita->confirmado_por_paciente)
    <form method="POST" action="{{ route('paciente.citas.confirmar', $cita->id) }}" class="mt-2">
        @csrf
        @method('PATCH')
        <button type="submit"
                class="w-full bg-green-500 text-white text-sm font-medium py-2.5 rounded-xl hover:bg-green-600 transition-all duration-300">
            ✓ Confirmar mi asistencia
        </button>
    </form>
@endif

{{-- Aviso de confirmación hecha --}}
@if($cita->status === 'confirmada' && $cita->confirmado_por_paciente)
    <div class="mt-2 bg-green-50 text-green-600 text-xs font-medium py-2 px-3 rounded-xl text-center">
        ✓ Asistencia confirmada
    </div>
@endif

{{-- Cancelar (solo si está pendiente o confirmada) --}}
@if(in_array($cita->status, ['pendiente', 'confirmada']))
    <form method="POST" action="{{ route('paciente.citas.cancelar', $cita->id) }}"
          onsubmit="return confirm('¿Seguro que deseas cancelar esta cita?');"
          class="mt-2">
        @csrf
        @method('PATCH')
        <button type="submit"
                class="w-full border border-red-200 text-red-500 text-sm font-medium py-2.5 rounded-xl hover:bg-red-50 transition-all duration-300">
            Cancelar cita
        </button>
    </form>
@endif
            </div>
            @endforeach
        </div>
    @endif

</x-paciente.layout>