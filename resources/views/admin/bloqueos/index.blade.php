<x-admin.layout title="Bloqueos">

@php
    $conflictos = session('conflictos', []);
    $campo = 'w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300';

    // Texto del periodo que cubre un bloqueo
    $periodo = function ($bloqueo) {
        $inicio = $bloqueo->fecha_inicio->format('d/m/Y');

        if (!$bloqueo->esDiaCompleto()) {
            return $inicio . ', ' . \Carbon\Carbon::parse($bloqueo->hora_inicio)->format('h:i A')
                . ' a ' . \Carbon\Carbon::parse($bloqueo->hora_fin)->format('h:i A');
        }

        return $bloqueo->fecha_inicio->equalTo($bloqueo->fecha_fin)
            ? $inicio . ', día completo'
            : $inicio . ' al ' . $bloqueo->fecha_fin->format('d/m/Y');
    };
@endphp

    <div x-data="{ tipo: {{ Illuminate\Support\Js::from(old('tipo', 'dia')) }} }">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="font-bold text-gray-800 text-2xl">Bloqueos de agenda</h1>
            <p class="font-light text-gray-400 text-sm mt-1">Vacaciones, días cerrados o juntas: en estos horarios nadie podrá agendar</p>
        </div>

        {{-- Formulario --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <h2 class="font-bold text-gray-800 text-base mb-4">Nuevo bloqueo</h2>

            <form method="POST" action="{{ route('admin.bloqueos.store') }}" class="flex flex-col gap-4">
                @csrf

                {{-- Tipo --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    @foreach(['dia' => 'Un día completo', 'rango' => 'Varios días (vacaciones)', 'horas' => 'Solo unas horas'] as $valor => $texto)
                        <label class="cursor-pointer">
                            <input type="radio" name="tipo" value="{{ $valor }}" x-model="tipo" class="sr-only">
                            <span class="block text-center text-sm font-medium px-4 py-3 rounded-xl border transition-all duration-300"
                                  :class="tipo === '{{ $valor }}' ? 'border-[#d75078] bg-[#fde7ed] text-[#d75078]' : 'border-gray-200 text-gray-500 hover:border-[#f285a2]'">
                                {{ $texto }}
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-gray-600 text-sm font-medium" x-text="tipo === 'rango' ? 'Desde' : 'Fecha'"></label>
                        <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio') }}" min="{{ now()->toDateString() }}" class="{{ $campo }}">
                        @error('fecha_inicio') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5" x-show="tipo === 'rango'" x-cloak>
                        <label class="text-gray-600 text-sm font-medium">Hasta (incluido)</label>
                        <input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}" min="{{ now()->toDateString() }}" class="{{ $campo }}" :disabled="tipo !== 'rango'">
                        @error('fecha_fin') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5" x-show="tipo === 'horas'" x-cloak>
                        <label class="text-gray-600 text-sm font-medium">De</label>
                        <input type="time" name="hora_inicio" value="{{ old('hora_inicio') }}" class="{{ $campo }}" :disabled="tipo !== 'horas'">
                        @error('hora_inicio') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5" x-show="tipo === 'horas'" x-cloak>
                        <label class="text-gray-600 text-sm font-medium">A</label>
                        <input type="time" name="hora_fin" value="{{ old('hora_fin') }}" class="{{ $campo }}" :disabled="tipo !== 'horas'">
                        @error('hora_fin') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Motivo <span class="text-gray-400 font-normal">(opcional)</span></label>
                    <input type="text" name="motivo" value="{{ old('motivo') }}" maxlength="255" placeholder="Ej. Vacaciones, junta, capacitación"
                           class="{{ $campo }}">
                    @error('motivo') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>

                @if(count($conflictos) > 0)
                    {{-- Aviso: ya hay citas en el periodo. Aún no se guardó nada. --}}
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 flex flex-col gap-3">
                        <div>
                            <p class="text-yellow-800 text-sm font-semibold">
                                Hay {{ count($conflictos) }} {{ count($conflictos) === 1 ? 'cita agendada' : 'citas agendadas' }} en ese periodo
                            </p>
                            <p class="text-yellow-700 text-xs mt-1">El bloqueo aún no se guardó. Elige qué hacer con ellas.</p>
                        </div>

                        <div class="flex flex-col gap-2 max-h-60 overflow-y-auto">
                            @foreach($conflictos as $c)
                                <input type="hidden" name="citas_vistas[]" value="{{ $c['id'] }}">
                                <div class="bg-white rounded-lg px-3 py-2 text-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                                    <span class="text-gray-700 font-medium">{{ $c['paciente'] }}</span>
                                    <span class="text-gray-500 text-xs">{{ $c['fecha'] }} · {{ $c['hora'] }} · {{ $c['servicio'] }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <button type="submit" name="accion_citas" value="conservar"
                                    class="flex-1 bg-[#d75078] text-white text-sm font-medium px-5 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                                Bloquear y conservar las citas
                            </button>
                            <button type="submit" name="accion_citas" value="cancelar"
                                    onclick="return confirm('Se cancelarán estas citas y los pacientes NO reciben aviso automático. ¿Continuar?');"
                                    class="flex-1 bg-red-50 text-red-600 text-sm font-medium px-5 py-3 rounded-xl hover:bg-red-100 transition-all duration-300">
                                Bloquear y cancelar las citas
                            </button>
                        </div>
                        <p class="text-yellow-700 text-xs">Si las conservas, quedan marcadas "En periodo bloqueado" en la lista de citas para reagendarlas.</p>
                    </div>
                @else
                    <div>
                        <button type="submit"
                                class="w-full sm:w-auto bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                            Guardar bloqueo
                        </button>
                    </div>
                @endif
            </form>
        </div>

        {{-- Bloqueos vigentes --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <h2 class="font-bold text-gray-800 text-base mb-4">Bloqueos vigentes</h2>

            @if($vigentes->isEmpty())
                <p class="text-gray-400 text-sm py-6 text-center">No hay bloqueos programados.</p>
            @else
                @include('admin.bloqueos.partials.lista', ['bloqueos' => $vigentes, 'periodo' => $periodo])
            @endif
        </div>

        {{-- Bloqueos pasados --}}
        @if($pasados->isNotEmpty())
            <details class="bg-white rounded-2xl shadow-sm p-6">
                <summary class="font-bold text-gray-800 text-base cursor-pointer">Bloqueos pasados</summary>
                <div class="mt-4">
                    @include('admin.bloqueos.partials.lista', ['bloqueos' => $pasados, 'periodo' => $periodo])
                </div>
            </details>
        @endif

    </div>

</x-admin.layout>
