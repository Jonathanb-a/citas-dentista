{{-- Odontograma de dentadura permanente (32 dientes, notación FDI) --}}
@php
    use App\Models\Odontograma;

    $erroresOdontograma = $errors->odontograma;
    $marcados = $expediente ? $expediente->odontograma->keyBy('numero_diente') : collect();
    $viejos = old('dientes', []);

    // Estado inicial de cada diente: lo enviado tras un error, si no lo guardado, si no sano
    $dientesIniciales = [];
    // Lo que está en la base de datos, para saber si hay cambios sin guardar
    $guardados = [];

    foreach (Odontograma::numerosValidos() as $numero) {
        $registro = $marcados->get($numero);
        $autor = $registro?->actualizadoPor;

        $dientesIniciales[$numero] = [
            'estado' => $viejos[$numero]['estado'] ?? $registro?->estado ?? 'sano',
            'nota' => $viejos[$numero]['nota'] ?? $registro?->nota ?? '',
            'autor' => $autor ? trim($autor->name . ' ' . $autor->apellidos) : null,
            'fecha' => $registro?->updated_at?->format('d/m/Y h:i A'),
        ];

        $guardados[$numero] = [
            'estado' => $registro?->estado ?? 'sano',
            'nota' => $registro?->nota ?? '',
        ];
    }

    $ultimoCambio = $marcados->sortByDesc('updated_at')->first();
@endphp

<div x-data="{
        estados: {{ Illuminate\Support\Js::from(Odontograma::ESTADOS) }},
        dientes: {{ Illuminate\Support\Js::from($dientesIniciales) }},
        guardados: {{ Illuminate\Support\Js::from($guardados) }},
        dienteAbierto: null,
        borrador: { estado: 'sano', nota: '' },

        etiqueta(estado) { return this.estados[estado]?.etiqueta ?? estado },
        colorRelleno(estado) { return this.estados[estado]?.relleno ?? '#ffffff' },
        colorBorde(estado) { return this.estados[estado]?.borde ?? '#d1d5db' },

        abrirDiente(numero) {
            this.dienteAbierto = numero;
            this.borrador = { estado: this.dientes[numero].estado, nota: this.dientes[numero].nota || '' };
        },
        aplicar() {
            const diente = this.dientes[this.dienteAbierto];
            diente.estado = this.borrador.estado;
            diente.nota = this.borrador.estado === 'sano' ? '' : this.borrador.nota;
            this.dienteAbierto = null;
        },
        quitarMarca() {
            this.borrador = { estado: 'sano', nota: '' };
            this.aplicar();
        },

        // Conteo por estado, recalculado mientras se marca (antes de guardar)
        get resumen() {
            const cuenta = {};
            Object.keys(this.estados).forEach(estado => cuenta[estado] = 0);
            Object.values(this.dientes).forEach(diente => cuenta[diente.estado]++);
            return cuenta;
        },
        get sinGuardar() {
            return Object.keys(this.dientes).some(numero =>
                this.dientes[numero].estado !== this.guardados[numero].estado ||
                (this.dientes[numero].nota || '') !== (this.guardados[numero].nota || '')
            );
        }
     }"
     x-init="window.addEventListener('beforeunload', (evento) => { if (sinGuardar) { evento.preventDefault(); evento.returnValue = ''; } })">

    @if($erroresOdontograma->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">
            <ul class="list-disc list-inside">
                @foreach($erroresOdontograma->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Resumen --}}
    <div class="flex flex-wrap items-center gap-2 mb-5">
        <span class="bg-gray-50 text-gray-500 text-xs font-medium px-3 py-1.5 rounded-full">
            <span x-text="resumen.sano"></span> sanos
        </span>
        <template x-for="(datos, estado) in estados" :key="estado">
            <span x-show="estado !== 'sano' && resumen[estado] > 0"
                  class="text-xs font-medium px-3 py-1.5 rounded-full"
                  :style="'background-color:' + datos.relleno + '; color:' + (datos.textoClaro ? '#ffffff' : '#374151')">
                <span x-text="resumen[estado]"></span> <span x-text="datos.etiqueta.toLowerCase()"></span>
            </span>
        </template>
        <span x-show="sinGuardar" x-cloak class="bg-amber-50 text-amber-700 text-xs font-medium px-3 py-1.5 rounded-full">
            Tienes cambios sin guardar
        </span>
    </div>

    <form method="POST" action="{{ route('admin.pacientes.odontograma.update', $paciente->id) }}">
        @csrf
        @method('PUT')

        {{-- Los estados viajan en campos ocultos ligados al diagrama --}}
        @foreach(Odontograma::numerosValidos() as $numero)
            <input type="hidden" name="dientes[{{ $numero }}][estado]" :value="dientes[{{ $numero }}].estado">
            <input type="hidden" name="dientes[{{ $numero }}][nota]" :value="dientes[{{ $numero }}].nota">
        @endforeach

        {{-- Diagrama --}}
        <div class="bg-[#FFF6F6] rounded-2xl p-3 sm:p-5 flex flex-col gap-4 overflow-hidden">

            {{-- Arcada superior --}}
            <div>
                <p class="text-gray-400 text-xs font-medium mb-2 text-center">ARCADA SUPERIOR</p>
                <div class="grid grid-cols-8 md:grid-cols-16 gap-x-0.5 gap-y-2 justify-items-center">
                    @foreach(Odontograma::CUADRANTES['superior_derecho'] as $numero)
                        <div class="{{ $loop->last ? 'md:mr-3' : '' }}">
                            @include('admin.pacientes.partials.diente', ['numero' => $numero, 'arcada' => 'superior'])
                        </div>
                    @endforeach
                    @foreach(Odontograma::CUADRANTES['superior_izquierdo'] as $numero)
                        <div class="{{ $loop->first ? 'md:ml-3' : '' }}">
                            @include('admin.pacientes.partials.diente', ['numero' => $numero, 'arcada' => 'superior'])
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-dashed border-gray-200"></div>

            {{-- Arcada inferior --}}
            <div>
                <div class="grid grid-cols-8 md:grid-cols-16 gap-x-0.5 gap-y-2 justify-items-center">
                    @foreach(Odontograma::CUADRANTES['inferior_derecho'] as $numero)
                        <div class="{{ $loop->last ? 'md:mr-3' : '' }}">
                            @include('admin.pacientes.partials.diente', ['numero' => $numero, 'arcada' => 'inferior'])
                        </div>
                    @endforeach
                    @foreach(Odontograma::CUADRANTES['inferior_izquierdo'] as $numero)
                        <div class="{{ $loop->first ? 'md:ml-3' : '' }}">
                            @include('admin.pacientes.partials.diente', ['numero' => $numero, 'arcada' => 'inferior'])
                        </div>
                    @endforeach
                </div>
                <p class="text-gray-400 text-xs font-medium mt-2 text-center">ARCADA INFERIOR</p>
            </div>
        </div>

        {{-- Leyenda --}}
        <div class="flex flex-wrap gap-x-4 gap-y-2 mt-4">
            @foreach(Odontograma::ESTADOS as $clave => $estado)
                <span class="flex items-center gap-2 text-gray-500 text-xs">
                    <span class="w-4 h-4 rounded border shrink-0" style="background-color: {{ $estado['relleno'] }}; border-color: {{ $estado['borde'] }}"></span>
                    {{ $estado['etiqueta'] }}{{ $clave === 'ausente' ? ' (tachado)' : '' }}
                </span>
            @endforeach
            <span class="flex items-center gap-2 text-gray-500 text-xs">
                <span class="w-2.5 h-2.5 rounded-full bg-[#d75078] shrink-0"></span>
                Con nota
            </span>
        </div>

        {{-- Pie: trazabilidad y guardar --}}
        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-4 mt-6 pt-6 border-t border-gray-50">
            <p class="text-gray-400 text-xs">
                @if($ultimoCambio)
                    Última actualización: {{ $ultimoCambio->updated_at->format('d/m/Y h:i A') }}
                    por {{ $ultimoCambio->actualizadoPor ? trim($ultimoCambio->actualizadoPor->name . ' ' . $ultimoCambio->actualizadoPor->apellidos) : 'un usuario eliminado' }}
                @else
                    Sin dientes marcados todavía
                @endif
            </p>
            <button type="submit"
                    class="w-full sm:w-auto bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300"
                    :class="sinGuardar ? 'ring-2 ring-[#fde7ed]' : ''">
                Guardar odontograma
            </button>
        </div>
    </form>

    {{-- Modal del diente --}}
    <div x-show="dienteAbierto" x-cloak
         class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
         @click.self="dienteAbierto = null" @keydown.escape.window="dienteAbierto = null">
        <template x-if="dienteAbierto">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between gap-3 mb-5">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Diente <span x-text="dienteAbierto"></span></h3>
                        <p class="text-gray-400 text-xs mt-0.5" x-show="dientes[dienteAbierto].fecha">
                            Marcado el <span x-text="dientes[dienteAbierto].fecha"></span>
                            por <span x-text="dientes[dienteAbierto].autor || 'un usuario eliminado'"></span>
                        </p>
                    </div>
                    <button type="button" @click="dienteAbierto = null" class="text-gray-400 hover:text-gray-600 shrink-0">✕</button>
                </div>

                {{-- Estados --}}
                <div class="grid grid-cols-2 gap-2 mb-4">
                    <template x-for="(datos, estado) in estados" :key="estado">
                        <button type="button" @click="borrador.estado = estado"
                                class="flex items-center gap-2 px-3 py-2.5 rounded-xl border text-left text-sm font-medium transition-all duration-200"
                                :class="borrador.estado === estado ? 'border-[#d75078] bg-[#fde7ed] text-[#d75078]' : 'border-gray-200 text-gray-600 hover:bg-[#FFF6F6]'">
                            <span class="w-4 h-4 rounded border shrink-0"
                                  :style="'background-color:' + datos.relleno + '; border-color:' + datos.borde"></span>
                            <span x-text="datos.etiqueta" class="text-xs"></span>
                        </button>
                    </template>
                </div>

                {{-- Nota --}}
                <div class="flex flex-col gap-1.5 mb-5" x-show="borrador.estado !== 'sano'">
                    <label class="text-gray-600 text-sm font-medium">Nota de este diente</label>
                    <textarea x-model="borrador.nota" rows="3" maxlength="500"
                              placeholder="Ej. cara oclusal, requiere radiografía…"
                              class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300 resize-y"></textarea>
                    <span class="text-gray-400 text-xs"><span x-text="(borrador.nota || '').length"></span>/500</span>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" @click="quitarMarca()"
                            x-show="dientes[dienteAbierto].estado !== 'sano'"
                            class="flex-1 border border-gray-200 text-gray-500 text-sm font-medium py-3 rounded-xl hover:bg-gray-50 transition-all duration-300">
                        Quitar marca
                    </button>
                    <button type="button" @click="aplicar()"
                            class="flex-1 bg-[#d75078] text-white text-sm font-medium py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                        Aceptar
                    </button>
                </div>

                <p class="text-gray-400 text-xs mt-4 text-center">Los cambios se guardan con el botón "Guardar odontograma".</p>
            </div>
        </template>
    </div>
</div>
