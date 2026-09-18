{{-- Expediente del paciente en solo lectura.
     No se muestran notas_doctora ni observaciones: son el espacio de trabajo
     interno de la doctora. Lo que el paciente necesita saber está en el
     diagnóstico, el plan de tratamiento y las indicaciones de cada consulta. --}}
@php
    $expediente = $paciente->expediente;
    $notas = $paciente->notasEvolucion;
    $dientesMarcados = $expediente ? $expediente->odontograma->keyBy('numero_diente') : collect();

    $siNo = fn ($valor) => is_null($valor) ? 'Sin registrar' : ($valor ? 'Sí' : 'No');
    $texto = fn ($valor) => filled($valor) ? $valor : 'Sin información';
    $tieneContenido = $expediente || $notas->isNotEmpty();
@endphp

<div class="flex flex-col gap-6">

    @if(!$tieneContenido)
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex flex-col items-center justify-center py-12 gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-gray-400 text-sm">Todavía no tienes información clínica registrada</p>
                <p class="text-gray-300 text-xs text-center max-w-sm">Tu expediente se llena en consulta. Aquí podrás consultarlo cuando la doctora lo registre.</p>
            </div>
        </div>
    @else
        <div class="bg-[#FFF6F6] rounded-2xl p-4 flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#d75078] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-gray-500 text-sm">
                Esta información la registra tu doctora en consulta, así que no se puede editar desde aquí.
                Si algo no coincide, coméntalo en tu próxima cita.
            </p>
        </div>

        {{-- Antecedentes --}}
        @if($expediente)
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-bold text-gray-800 text-lg mb-4">Antecedentes</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach([
                        'antecedentes_familiares' => 'HEREDOFAMILIARES',
                        'alergias' => 'ALERGIAS',
                        'enfermedades' => 'ENFERMEDADES',
                        'medicamentos' => 'MEDICAMENTOS',
                        'cirugias_previas' => 'CIRUGÍAS PREVIAS',
                    ] as $campo => $etiqueta)
                        <div>
                            <dt class="text-gray-400 text-xs font-medium">{{ $etiqueta }}</dt>
                            <dd class="text-sm mt-1 whitespace-pre-line {{ $expediente->$campo ? 'text-gray-700' : 'text-gray-300' }}">{{ $texto($expediente->$campo) }}</dd>
                        </div>
                    @endforeach
                    @if(($paciente->sexo ?? $expediente->sexo) !== 'masculino')
                        <div>
                            <dt class="text-gray-400 text-xs font-medium">EMBARAZADA</dt>
                            <dd class="text-gray-700 text-sm mt-1">{{ $siNo($expediente->embarazada) }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-gray-400 text-xs font-medium">TIPO DE SANGRE</dt>
                        <dd class="text-sm mt-1 {{ $expediente->tipo_sangre ? 'text-gray-700' : 'text-gray-300' }}">{{ $expediente->tipo_sangre ?: 'Sin registrar' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Hábitos --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-bold text-gray-800 text-lg mb-4">Hábitos</h2>
                <dl class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach([
                        'fuma' => 'FUMA',
                        'alcohol' => 'CONSUME ALCOHOL',
                        'bruxismo' => 'BRUXISMO',
                        'usa_hilo_dental' => 'USA HILO DENTAL',
                    ] as $campo => $etiqueta)
                        <div>
                            <dt class="text-gray-400 text-xs font-medium">{{ $etiqueta }}</dt>
                            <dd class="text-gray-700 text-sm mt-1">{{ $siNo($expediente->$campo) }}</dd>
                        </div>
                    @endforeach
                    <div>
                        <dt class="text-gray-400 text-xs font-medium">FRECUENCIA DE CEPILLADO</dt>
                        <dd class="text-sm mt-1 {{ $expediente->frecuencia_cepillado ? 'text-gray-700' : 'text-gray-300' }}">{{ $expediente->frecuencia_cepillado ?: 'Sin registrar' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Diagnóstico y plan --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-bold text-gray-800 text-lg mb-4">Diagnóstico y plan de tratamiento</h2>
                <dl class="flex flex-col gap-4">
                    @foreach([
                        'padecimiento_actual' => 'PADECIMIENTO ACTUAL',
                        'diagnostico' => 'DIAGNÓSTICO',
                        'plan_tratamiento' => 'PLAN DE TRATAMIENTO',
                    ] as $campo => $etiqueta)
                        <div>
                            <dt class="text-gray-400 text-xs font-medium">{{ $etiqueta }}</dt>
                            <dd class="text-sm mt-1 whitespace-pre-line {{ $expediente->$campo ? 'text-gray-700' : 'text-gray-300' }}">{{ $texto($expediente->$campo) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Odontograma --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-bold text-gray-800 text-lg mb-1">Mi odontograma</h2>
                <p class="text-gray-400 text-sm mb-4">Así está registrada tu dentadura</p>
                @include('paciente.perfil.partials.odontograma-lectura')
            </div>
        @endif

        {{-- Notas de evolución --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-bold text-gray-800 text-lg mb-1">Mis consultas</h2>
            <p class="text-gray-400 text-sm mb-4">Qué se hizo en cada visita y qué debes hacer en casa</p>

            @if($notas->isEmpty())
                <p class="text-gray-300 text-sm py-6 text-center">Todavía no hay consultas registradas.</p>
            @else
                <div class="flex flex-col">
                    @foreach($notas as $nota)
                        @php($cita = $nota->cita)
                        <div class="flex gap-4">
                            <div class="flex flex-col items-center shrink-0">
                                <span class="w-3 h-3 rounded-full bg-[#d75078] mt-1.5"></span>
                                @if(!$loop->last)
                                    <span class="w-px flex-1 bg-gray-100"></span>
                                @endif
                            </div>

                            <div class="flex-1 pb-6 min-w-0">
                                <div class="border border-gray-100 rounded-xl p-4 flex flex-col gap-3">
                                    <div>
                                        <p class="text-gray-800 text-sm font-medium">
                                            {{ $cita ? \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') : 'Consulta' }}
                                        </p>
                                        <p class="text-gray-400 text-xs">
                                            {{ $cita?->servicio?->nombre ?? '—' }}
                                            @if($cita?->servicio?->especialidad)
                                                · {{ $cita->servicio->especialidad->nombre }}
                                            @endif
                                        </p>
                                    </div>

                                    <dl class="flex flex-col gap-3">
                                        @foreach([
                                            'procedimiento' => 'QUÉ SE TE HIZO',
                                            'observaciones' => 'OBSERVACIONES',
                                            'indicaciones' => 'INDICACIONES PARA CASA',
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
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
