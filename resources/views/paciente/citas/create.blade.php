<x-paciente.layout title="Agendar cita">

    <div x-data="agendarCita()">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="font-bold text-gray-800 text-2xl">Agendar nueva cita</h1>
            <p class="font-light text-gray-400 text-sm mt-1">Sigue los pasos para reservar tu cita</p>
        </div>

        {{-- Errores --}}
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                @foreach ($errors->all() as $error)
                    <p class="text-red-500 text-sm">• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Banner de valoración para quien no sabe qué necesita --}}
        @php
            $valoracion = $especialidades->flatMap->servicios
                ->firstWhere('nombre', 'Consulta de valoración');
        @endphp

        @if($valoracion)
        <button type="button"
                @click="seleccionarServicio({{ $valoracion->id }}, '{{ $valoracion->nombre }}', {{ $valoracion->duracion_minutos }})"
                class="mb-6 w-full text-left bg-gradient-to-r from-[#d75078] to-[#f285a2] rounded-2xl p-5 shadow-sm hover:shadow-md transition-all duration-300 flex items-center gap-4">
            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-white font-semibold text-sm">¿No sabes qué necesitas?</p>
                <p class="text-white/85 text-xs mt-0.5">Agenda una <strong>consulta de valoración</strong> ({{ $valoracion->duracion_minutos }} min). La doctora evaluará tu molestia y te dirá el tratamiento adecuado.</p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        @endif

        <form method="POST" action="{{ route('paciente.citas.store') }}" class="flex flex-col gap-6">
            @csrf
            <input type="hidden" name="servicio_id" :value="servicioId">
            <input type="hidden" name="horario_id" :value="horarioId">
            <input type="hidden" name="fecha" :value="fecha">
            <input type="hidden" name="hora" :value="horaSeleccionada">

            {{-- Paso 1: Especialidad y Servicio --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-bold text-gray-800 text-base mb-4">1. Elige el servicio</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($especialidades as $esp)
                        @foreach($esp->servicios as $serv)
                        <button type="button"
                                @click="seleccionarServicio({{ $serv->id }}, '{{ $serv->nombre }}', {{ $serv->duracion_minutos }})"
                                class="text-left p-4 rounded-xl border transition-all duration-300"
                                :class="servicioId === {{ $serv->id }} ? 'border-[#d75078] bg-[#fde7ed]' : 'border-gray-200 hover:border-[#f285a2]'">
                            <p class="font-medium text-gray-800 text-sm">{{ $serv->nombre }}</p>
                            <p class="text-gray-400 text-xs mt-1">{{ $esp->nombre }} · {{ $serv->duracion_minutos }} min</p>
                        </button>
                        @endforeach
                    @endforeach
                </div>
            </div>

            {{-- Paso 2: Fecha --}}
            <div class="bg-white rounded-2xl shadow-sm p-6" x-show="servicioId" x-cloak>
                <h2 class="font-bold text-gray-800 text-base mb-4">2. Elige la fecha</h2>
                <input type="date"
                       x-model="fecha"
                       @change="buscarDisponibilidad()"
                       :min="hoy"
                       class="w-full md:w-auto px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
            </div>

            {{-- Paso 3: Horarios disponibles --}}
            <div class="bg-white rounded-2xl shadow-sm p-6" x-show="fecha && servicioId" x-cloak>
                <h2 class="font-bold text-gray-800 text-base mb-4">3. Elige el horario</h2>

                {{-- Cargando --}}
                <div x-show="cargando" class="text-gray-400 text-sm">Buscando horarios disponibles...</div>

                {{-- Sin espacios --}}
                <div x-show="!cargando && espacios.length === 0" class="text-gray-400 text-sm">
                    No hay horarios disponibles para esta fecha. Prueba con otro día.
                </div>

                {{-- Espacios --}}
                <div x-show="!cargando && espacios.length > 0" class="grid grid-cols-3 md:grid-cols-5 gap-3">
                    <template x-for="espacio in espacios" :key="espacio.hora">
                        <button type="button"
                                @click="seleccionarHora(espacio)"
                                class="py-2.5 px-3 rounded-xl border text-sm transition-all duration-300"
                                :class="horaSeleccionada === espacio.hora ? 'border-[#d75078] bg-[#d75078] text-white' : 'border-gray-200 text-gray-600 hover:border-[#f285a2]'">
                            <span x-text="espacio.hora_formato"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Paso 4: Motivo (opcional) --}}
            <div class="bg-white rounded-2xl shadow-sm p-6" x-show="horaSeleccionada" x-cloak>
                <h2 class="font-bold text-gray-800 text-base mb-4">4. Motivo de la consulta (opcional)</h2>
                <textarea name="motivo" rows="3"
                          :placeholder="servicioNombre === 'Consulta de valoración'
                              ? 'Cuéntanos qué molestia tienes: dónde te duele, desde cuándo, etc. Esto ayudará a la doctora a prepararse.'
                              : 'Describe brevemente el motivo de tu visita'"
                          class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300 resize-none"></textarea>
            </div>

            {{-- Botón --}}
            <div x-show="horaSeleccionada" x-cloak>
                <button type="submit"
                        class="w-full md:w-auto bg-[#d75078] text-white text-sm font-semibold px-8 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                    Confirmar cita
                </button>
            </div>

        </form>

    </div>

    <script>
        function agendarCita() {
            return {
                servicioId: null,
                servicioNombre: '',
                duracion: 0,
                fecha: '',
                hoy: new Date().toISOString().split('T')[0],
                espacios: [],
                horaSeleccionada: null,
                horarioId: null,
                cargando: false,

                seleccionarServicio(id, nombre, duracion) {
                    this.servicioId = id;
                    this.servicioNombre = nombre;
                    this.duracion = duracion;
                    this.fecha = '';
                    this.espacios = [];
                    this.horaSeleccionada = null;
                },

                async buscarDisponibilidad() {
                    if (!this.fecha || !this.servicioId) return;
                    this.cargando = true;
                    this.espacios = [];
                    this.horaSeleccionada = null;

                    try {
                        const res = await fetch('{{ route('paciente.citas.disponibilidad') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                fecha: this.fecha,
                                servicio_id: this.servicioId
                            })
                        });
                        this.espacios = await res.json();
                    } catch (e) {
                        console.error(e);
                    }
                    this.cargando = false;
                },

                seleccionarHora(espacio) {
                    this.horaSeleccionada = espacio.hora;
                    this.horarioId = espacio.horario_id;
                }
            }
        }
    </script>

</x-paciente.layout>