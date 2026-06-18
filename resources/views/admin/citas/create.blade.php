<x-admin.layout title="Nueva cita">

    <div x-data="crearCita()">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="font-bold text-gray-800 text-2xl">Nueva cita</h1>
            <p class="font-light text-gray-400 text-sm mt-1">Agenda una cita manualmente para un paciente</p>
        </div>

        {{-- Errores --}}
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                @foreach ($errors->all() as $error)
                    <p class="text-red-500 text-sm">• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.citas.store') }}" class="flex flex-col gap-6">
            @csrf
            <input type="hidden" name="servicio_id" :value="servicioId">
            <input type="hidden" name="horario_id" :value="horarioId">
            <input type="hidden" name="fecha" :value="fecha">
            <input type="hidden" name="hora" :value="horaSeleccionada">

            {{-- Paso 1: Paciente --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-bold text-gray-800 text-base mb-4">1. Paciente</h2>

                {{-- Selector tipo paciente --}}
                <div class="flex gap-3 mb-4">
                    <button type="button" @click="tipoPaciente = 'existente'"
                            class="flex-1 py-2.5 rounded-xl border text-sm font-medium transition-all duration-300"
                            :class="tipoPaciente === 'existente' ? 'border-[#d75078] bg-[#fde7ed] text-[#d75078]' : 'border-gray-200 text-gray-500'">
                        Paciente existente
                    </button>
                    <button type="button" @click="tipoPaciente = 'nuevo'"
                            class="flex-1 py-2.5 rounded-xl border text-sm font-medium transition-all duration-300"
                            :class="tipoPaciente === 'nuevo' ? 'border-[#d75078] bg-[#fde7ed] text-[#d75078]' : 'border-gray-200 text-gray-500'">
                        Paciente nuevo
                    </button>
                </div>
                <input type="hidden" name="tipo_paciente" :value="tipoPaciente">

                {{-- Paciente existente --}}
                <div x-show="tipoPaciente === 'existente'">
                    <select name="paciente_id"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                        <option value="">Selecciona un paciente</option>
                        @foreach($pacientes as $paciente)
                            <option value="{{ $paciente->id }}">{{ $paciente->name }} {{ $paciente->apellidos }} — {{ $paciente->telefono }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Paciente nuevo --}}
                <div x-show="tipoPaciente === 'nuevo'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-gray-600 text-sm font-medium">Nombre</label>
                        <input type="text" name="nuevo_nombre"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-gray-600 text-sm font-medium">Apellidos</label>
                        <input type="text" name="nuevo_apellidos"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-gray-600 text-sm font-medium">Teléfono</label>
                        <input type="text" name="nuevo_telefono"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-gray-600 text-sm font-medium">Correo</label>
                        <input type="email" name="nuevo_email"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                    </div>
                    <p class="text-gray-400 text-xs md:col-span-2">Se creará una cuenta con contraseña temporal: <strong>paciente123</strong></p>
                </div>
            </div>

            {{-- Paso 2: Servicio --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-bold text-gray-800 text-base mb-4">2. Servicio</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($especialidades as $esp)
                        @foreach($esp->servicios as $serv)
                        <button type="button"
                                @click="seleccionarServicio({{ $serv->id }}, {{ $serv->duracion_minutos }})"
                                class="text-left p-4 rounded-xl border transition-all duration-300"
                                :class="servicioId === {{ $serv->id }} ? 'border-[#d75078] bg-[#fde7ed]' : 'border-gray-200 hover:border-[#f285a2]'">
                            <p class="font-medium text-gray-800 text-sm">{{ $serv->nombre }}</p>
                            <p class="text-gray-400 text-xs mt-1">{{ $esp->nombre }} · {{ $serv->duracion_minutos }} min</p>
                        </button>
                        @endforeach
                    @endforeach
                </div>
            </div>

            {{-- Paso 3: Fecha --}}
            <div class="bg-white rounded-2xl shadow-sm p-6" x-show="servicioId" x-cloak>
                <h2 class="font-bold text-gray-800 text-base mb-4">3. Fecha</h2>
                <input type="date" x-model="fecha" @change="buscarDisponibilidad()" :min="hoy"
                       class="w-full md:w-auto px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
            </div>

            {{-- Paso 4: Horario --}}
            <div class="bg-white rounded-2xl shadow-sm p-6" x-show="fecha && servicioId" x-cloak>
                <h2 class="font-bold text-gray-800 text-base mb-4">4. Horario</h2>
                <div x-show="cargando" class="text-gray-400 text-sm">Buscando horarios...</div>
                <div x-show="!cargando && espacios.length === 0" class="text-gray-400 text-sm">No hay horarios disponibles para esta fecha.</div>
                <div x-show="!cargando && espacios.length > 0" class="grid grid-cols-3 md:grid-cols-5 gap-3">
                    <template x-for="espacio in espacios" :key="espacio.hora">
                        <button type="button" @click="seleccionarHora(espacio)"
                                class="py-2.5 px-3 rounded-xl border text-sm transition-all duration-300"
                                :class="horaSeleccionada === espacio.hora ? 'border-[#d75078] bg-[#d75078] text-white' : 'border-gray-200 text-gray-600 hover:border-[#f285a2]'">
                            <span x-text="espacio.hora_formato"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Paso 5: Estado y motivo --}}
            <div class="bg-white rounded-2xl shadow-sm p-6" x-show="horaSeleccionada" x-cloak>
                <h2 class="font-bold text-gray-800 text-base mb-4">5. Detalles</h2>

                <div class="flex flex-col gap-1.5 mb-4">
                    <label class="text-gray-600 text-sm font-medium">Estado de la cita</label>
                    <select name="status"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                        <option value="confirmada">Confirmada (la clínica la agenda directamente)</option>
                        <option value="pendiente">Pendiente (requiere confirmación)</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Motivo (opcional)</label>
                    <textarea name="motivo" rows="2"
                              class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300 resize-none"></textarea>
                </div>
            </div>

            {{-- Botón --}}
            <div x-show="horaSeleccionada" x-cloak class="flex gap-3">
                <a href="{{ route('admin.citas.index') }}"
                   class="border border-gray-200 text-gray-500 text-sm font-medium px-6 py-3 rounded-xl hover:bg-gray-50 transition-all duration-300">
                    Cancelar
                </a>
                <button type="submit"
                        class="bg-[#d75078] text-white text-sm font-semibold px-8 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                    Crear cita
                </button>
            </div>

        </form>
    </div>

    <script>
        function crearCita() {
            return {
                tipoPaciente: 'existente',
                servicioId: null,
                duracion: 0,
                fecha: '',
                hoy: new Date().toISOString().split('T')[0],
                espacios: [],
                horaSeleccionada: null,
                horarioId: null,
                cargando: false,

                seleccionarServicio(id, duracion) {
                    this.servicioId = id;
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
                        const res = await fetch('{{ route('admin.citas.disponibilidad') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ fecha: this.fecha, servicio_id: this.servicioId })
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

</x-admin.layout>