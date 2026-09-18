{{-- Formulario de historia clínica (NOM-013-SSA2), organizado en pestañas --}}
@php
    $erroresExpediente = $errors->expediente;

    $pestanas = [
        'generales' => [
            'titulo' => 'Datos generales',
            'campos' => ['direccion', 'contacto_emergencia_nombre', 'contacto_emergencia_telefono', 'tipo_sangre'],
        ],
        'antecedentes' => [
            'titulo' => 'Antecedentes',
            'campos' => ['antecedentes_familiares', 'alergias', 'enfermedades', 'medicamentos', 'cirugias_previas', 'embarazada'],
        ],
        'habitos' => [
            'titulo' => 'Hábitos',
            'campos' => ['fuma', 'alcohol', 'bruxismo', 'frecuencia_cepillado', 'usa_hilo_dental'],
        ],
        'diagnostico' => [
            'titulo' => 'Diagnóstico y plan',
            'campos' => ['padecimiento_actual', 'diagnostico', 'plan_tratamiento', 'notas_doctora', 'observaciones'],
        ],
        // El odontograma tiene su propio formulario, aparte del de la historia clínica
        'odontograma' => [
            'titulo' => 'Odontograma',
            'campos' => [],
        ],
        // La evolución se edita desde su propio modal, por cita
        'evolucion' => [
            'titulo' => 'Evolución',
            'campos' => [],
        ],
    ];

    // Si hubo errores, abrir la pestaña que contiene el primero
    $pestanaInicial = 'generales';
    $primerError = $erroresExpediente->keys()[0] ?? null;
    foreach ($pestanas as $clave => $pestana) {
        if (in_array($primerError, $pestana['campos'])) {
            $pestanaInicial = $clave;
            break;
        }
    }

    // Un error del odontograma o de una nota de evolución abre su pestaña
    if ($errors->odontograma->any()) {
        $pestanaInicial = 'odontograma';
    }

    if (collect($errors->getBags())->keys()->contains(fn ($bag) => str_starts_with($bag, 'nota_'))) {
        $pestanaInicial = 'evolucion';
    }

    $claseInput = 'w-full px-4 py-3 rounded-xl border text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300';
    $borde = fn ($campo) => $erroresExpediente->has($campo) ? 'border-red-300' : 'border-gray-200';
    $valor = fn ($campo) => old($campo, $expediente?->$campo);

    $muestraEmbarazo = $sexo !== 'masculino';
@endphp

<div class="bg-white rounded-2xl shadow-sm p-6 mb-6" x-data="{ pestana: '{{ $pestanaInicial }}' }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
        <h2 class="font-bold text-gray-800 text-lg">Expediente clínico</h2>
        <span class="text-gray-400 text-xs">Historia clínica odontológica · NOM-013-SSA2</span>
    </div>

    @if(!$expediente)
        <div class="flex items-center gap-3 bg-[#FFF6F6] text-gray-500 text-sm px-4 py-3 rounded-xl mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#d75078] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Este paciente aún no tiene expediente. Llena los datos y guarda para crearlo.
        </div>
    @endif

    @if($erroresExpediente->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">
            <ul class="list-disc list-inside">
                @foreach($erroresExpediente->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Pestañas --}}
    <div class="flex gap-2 overflow-x-auto pb-2 mb-4 border-b border-gray-50">
        @foreach($pestanas as $clave => $pestana)
            <button type="button" @click="pestana = '{{ $clave }}'"
                    :class="pestana === '{{ $clave }}' ? 'bg-[#fde7ed] text-[#d75078]' : 'text-gray-400 hover:text-[#d75078] hover:bg-[#FFF6F6]'"
                    class="relative whitespace-nowrap px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200">
                {{ $pestana['titulo'] }}
                @if(collect($pestana['campos'])->contains(fn ($campo) => $erroresExpediente->has($campo)) || ($clave === 'odontograma' && $errors->odontograma->any()))
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Historia clínica: un solo formulario para sus cuatro pestañas --}}
    <form method="POST" action="{{ route('admin.pacientes.expediente.update', $paciente->id) }}"
          x-show="!['odontograma', 'evolucion'].includes(pestana)" x-cloak>
        @csrf
        @method('PUT')

        {{-- Datos generales --}}
        <div x-show="pestana === 'generales'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5 md:col-span-2">
                <label for="direccion" class="text-gray-600 text-sm font-medium">Dirección</label>
                <input type="text" id="direccion" name="direccion" value="{{ $valor('direccion') }}" maxlength="255"
                       placeholder="Calle, número, colonia, ciudad"
                       class="{{ $claseInput }} {{ $borde('direccion') }}">
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="contacto_emergencia_nombre" class="text-gray-600 text-sm font-medium">Contacto de emergencia</label>
                <input type="text" id="contacto_emergencia_nombre" name="contacto_emergencia_nombre" value="{{ $valor('contacto_emergencia_nombre') }}" maxlength="255"
                       placeholder="Nombre completo"
                       class="{{ $claseInput }} {{ $borde('contacto_emergencia_nombre') }}">
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="contacto_emergencia_telefono" class="text-gray-600 text-sm font-medium">Teléfono de emergencia</label>
                <input type="tel" id="contacto_emergencia_telefono" name="contacto_emergencia_telefono" value="{{ $valor('contacto_emergencia_telefono') }}" maxlength="20"
                       placeholder="Ej. 7531234567"
                       class="{{ $claseInput }} {{ $borde('contacto_emergencia_telefono') }}">
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="tipo_sangre" class="text-gray-600 text-sm font-medium">Tipo de sangre</label>
                <select id="tipo_sangre" name="tipo_sangre" class="{{ $claseInput }} {{ $borde('tipo_sangre') }} bg-white">
                    <option value="">Sin registrar</option>
                    @foreach(\App\Models\Expediente::TIPOS_SANGRE as $tipo)
                        <option value="{{ $tipo }}" @selected($valor('tipo_sangre') === $tipo)>{{ $tipo }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Antecedentes --}}
        <div x-show="pestana === 'antecedentes'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach([
                'antecedentes_familiares' => ['Antecedentes heredofamiliares', 'Diabetes, hipertensión, cardiopatías, cáncer en familiares directos…'],
                'alergias' => ['Alergias', 'Medicamentos, anestésicos, látex, alimentos…'],
                'enfermedades' => ['Enfermedades', 'Padecimientos actuales o crónicos'],
                'medicamentos' => ['Medicamentos', 'Medicamentos que toma actualmente y dosis'],
                'cirugias_previas' => ['Cirugías previas', 'Tipo de cirugía y año'],
            ] as $campo => [$etiqueta, $ayuda])
                <div class="flex flex-col gap-1.5 {{ $loop->first ? 'md:col-span-2' : '' }}">
                    <label for="{{ $campo }}" class="text-gray-600 text-sm font-medium">{{ $etiqueta }}</label>
                    <textarea id="{{ $campo }}" name="{{ $campo }}" rows="3" maxlength="5000" placeholder="{{ $ayuda }}"
                              class="{{ $claseInput }} {{ $borde($campo) }} resize-y">{{ $valor($campo) }}</textarea>
                </div>
            @endforeach

            @if($muestraEmbarazo)
                <div class="md:col-span-2">
                    @include('admin.pacientes.partials.toggle', ['campo' => 'embarazada', 'etiqueta' => 'Embarazada', 'marcado' => $valor('embarazada')])
                </div>
            @endif
        </div>

        {{-- Hábitos --}}
        <div x-show="pestana === 'habitos'" x-cloak class="flex flex-col gap-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach([
                    'fuma' => 'Fuma',
                    'alcohol' => 'Consume alcohol',
                    'bruxismo' => 'Bruxismo (aprieta o rechina los dientes)',
                    'usa_hilo_dental' => 'Usa hilo dental',
                ] as $campo => $etiqueta)
                    @include('admin.pacientes.partials.toggle', ['campo' => $campo, 'etiqueta' => $etiqueta, 'marcado' => $valor($campo)])
                @endforeach
            </div>
            <div class="flex flex-col gap-1.5 md:w-1/2">
                <label for="frecuencia_cepillado" class="text-gray-600 text-sm font-medium">Frecuencia de cepillado</label>
                <select id="frecuencia_cepillado" name="frecuencia_cepillado" class="{{ $claseInput }} {{ $borde('frecuencia_cepillado') }} bg-white">
                    <option value="">Sin registrar</option>
                    @foreach(\App\Models\Expediente::FRECUENCIAS_CEPILLADO as $frecuencia)
                        <option value="{{ $frecuencia }}" @selected($valor('frecuencia_cepillado') === $frecuencia)>{{ $frecuencia }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Diagnóstico y plan --}}
        <div x-show="pestana === 'diagnostico'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach([
                'padecimiento_actual' => ['Padecimiento actual', 'Motivo de consulta, síntomas y tiempo de evolución'],
                'diagnostico' => ['Diagnóstico', 'Diagnóstico clínico'],
                'plan_tratamiento' => ['Plan de tratamiento', 'Tratamientos propuestos en orden'],
                'notas_doctora' => ['Notas de la doctora', 'Notas internas'],
                'observaciones' => ['Observaciones', 'Otras observaciones'],
            ] as $campo => [$etiqueta, $ayuda])
                <div class="flex flex-col gap-1.5 {{ $loop->first ? 'md:col-span-2' : '' }}">
                    <label for="{{ $campo }}" class="text-gray-600 text-sm font-medium">{{ $etiqueta }}</label>
                    <textarea id="{{ $campo }}" name="{{ $campo }}" rows="4" maxlength="5000" placeholder="{{ $ayuda }}"
                              class="{{ $claseInput }} {{ $borde($campo) }} resize-y">{{ $valor($campo) }}</textarea>
                </div>
            @endforeach
        </div>

        {{-- Pie: trazabilidad y guardar --}}
        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-4 mt-6 pt-6 border-t border-gray-50">
            <p class="text-gray-400 text-xs">
                @if($expediente?->ultima_actualizacion)
                    Última actualización: {{ $expediente->ultima_actualizacion->format('d/m/Y h:i A') }}
                    por {{ $expediente->actualizadoPor ? trim($expediente->actualizadoPor->name . ' ' . $expediente->actualizadoPor->apellidos) : 'un usuario eliminado' }}
                @else
                    Sin actualizaciones registradas
                @endif
            </p>
            <button type="submit"
                    class="w-full sm:w-auto bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                Guardar expediente
            </button>
        </div>
    </form>

    {{-- Odontograma: formulario propio, hermano del anterior --}}
    <div x-show="pestana === 'odontograma'" x-cloak>
        @include('admin.pacientes.partials.odontograma')
    </div>

    {{-- Evolución: línea de tiempo de las consultas --}}
    <div x-show="pestana === 'evolucion'" x-cloak>
        @include('admin.pacientes.partials.evolucion')
    </div>
</div>
