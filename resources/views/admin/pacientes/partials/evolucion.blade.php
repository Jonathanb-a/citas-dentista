{{-- Línea de tiempo de notas de evolución, de la más reciente a la más antigua --}}
@php
    $notas = $paciente->notasEvolucion;

    // Si la validación falló al editar una nota, se reabre su modal con lo escrito
    $bagNota = collect($errors->getBags())->keys()->first(fn ($bag) => str_starts_with($bag, 'nota_'));
    $notaConError = $bagNota ? $notas->firstWhere('cita_id', (int) substr($bagNota, strlen('nota_'))) : null;

    $notaInicial = $notaConError ? [
        'citaId' => $notaConError->cita_id,
        'paciente' => trim($paciente->name . ' ' . $paciente->apellidos),
        'fecha' => $notaConError->cita ? \Carbon\Carbon::parse($notaConError->cita->fecha)->format('d/m/Y') : '',
        'servicio' => $notaConError->cita?->servicio?->nombre ?? '',
        'accion' => 'editar',
        'campos' => [
            'procedimiento' => old('procedimiento', ''),
            'observaciones' => old('observaciones', ''),
            'indicaciones' => old('indicaciones', ''),
            'proxima_cita_sugerida' => old('proxima_cita_sugerida', ''),
        ],
        'errores' => $errors->{$bagNota}->all(),
    ] : null;
@endphp

<div x-data="{ nota: {{ Illuminate\Support\Js::from($notaInicial) }}, abrirNota(datos) { this.nota = datos; } }">

    @if($notas->isEmpty())
        <div class="flex flex-col items-center justify-center py-12 gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-400 text-sm">Este paciente aún no tiene notas de evolución</p>
            <p class="text-gray-300 text-xs text-center max-w-sm">Se registran al marcar una cita como atendida, o después desde la lista de citas.</p>
        </div>
    @else
        <p class="text-gray-400 text-sm mb-5">
            {{ $notas->count() }} {{ $notas->count() === 1 ? 'consulta registrada' : 'consultas registradas' }}
        </p>

        <div class="flex flex-col">
            @foreach($notas as $nota)
                @php
                    $cita = $nota->cita;
                    $autor = $nota->creadoPor;
                    $datosNota = [
                        'citaId' => $nota->cita_id,
                        'paciente' => trim($paciente->name . ' ' . $paciente->apellidos),
                        'fecha' => $cita ? \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') : '',
                        'servicio' => $cita?->servicio?->nombre ?? '',
                        'accion' => 'editar',
                        'campos' => [
                            'procedimiento' => $nota->procedimiento ?? '',
                            'observaciones' => $nota->observaciones ?? '',
                            'indicaciones' => $nota->indicaciones ?? '',
                            'proxima_cita_sugerida' => $nota->proxima_cita_sugerida ?? '',
                        ],
                        'errores' => [],
                    ];
                @endphp

                <div class="flex gap-4">
                    {{-- Línea de tiempo --}}
                    <div class="flex flex-col items-center shrink-0">
                        <span class="w-3 h-3 rounded-full bg-[#d75078] mt-1.5"></span>
                        @if(!$loop->last)
                            <span class="w-px flex-1 bg-gray-100"></span>
                        @endif
                    </div>

                    <div class="flex-1 pb-6 min-w-0">
                        <div class="border border-gray-100 rounded-xl p-4 flex flex-col gap-3">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-gray-800 text-sm font-medium">
                                        {{ $cita ? \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') : 'Cita eliminada' }}
                                        @if($cita)
                                            <span class="text-gray-400 font-normal">{{ \Carbon\Carbon::parse($cita->hora)->format('h:i A') }}</span>
                                        @endif
                                    </p>
                                    <p class="text-gray-400 text-xs">
                                        {{ $cita?->servicio?->nombre ?? '—' }}
                                        @if($cita?->servicio?->especialidad)
                                            · {{ $cita->servicio->especialidad->nombre }}
                                        @endif
                                    </p>
                                </div>
                                <button type="button" @click="abrirNota({{ Illuminate\Support\Js::from($datosNota) }})"
                                        class="shrink-0 px-3 py-1.5 bg-[#fde7ed] text-[#d75078] text-xs font-medium rounded-lg hover:bg-[#fbd3df] transition-all duration-200">
                                    Editar
                                </button>
                            </div>

                            <dl class="flex flex-col gap-3">
                                @foreach([
                                    'procedimiento' => 'PROCEDIMIENTO',
                                    'observaciones' => 'OBSERVACIONES',
                                    'indicaciones' => 'INDICACIONES',
                                    'proxima_cita_sugerida' => 'PRÓXIMA CITA SUGERIDA',
                                ] as $campo => $etiqueta)
                                    @if($nota->$campo)
                                        <div>
                                            <dt class="text-gray-400 text-xs font-medium">{{ $etiqueta }}</dt>
                                            <dd class="text-gray-700 text-sm mt-1 whitespace-pre-line">{{ $nota->$campo }}</dd>
                                        </div>
                                    @endif
                                @endforeach
                            </dl>

                            <p class="text-gray-400 text-xs pt-2 border-t border-gray-50">
                                Escrita por {{ $autor ? trim($autor->name . ' ' . $autor->apellidos) : 'un usuario eliminado' }}
                                el {{ $nota->created_at->format('d/m/Y h:i A') }}
                                @if($nota->updated_at->gt($nota->created_at))
                                    · editada el {{ $nota->updated_at->format('d/m/Y h:i A') }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @include('admin.citas.partials.modal-nota')
</div>
