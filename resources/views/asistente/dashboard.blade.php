<x-asistente.layout title="Dashboard">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="font-bold text-gray-800 text-2xl">Hola, {{ auth()->user()->name }} 👋</h1>
            <p class="font-light text-gray-400 text-sm mt-1">{{ now()->translatedFormat('l, d \d\e F \d\e Y') }}</p>
        </div>
    </div>

    {{-- Tarjetas de estadisticas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

        <div class="bg-white rounded-2xl p-6 shadow-sm flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <span class="text-gray-400 text-sm font-medium">Citas hoy</span>
                <div class="w-10 h-10 bg-[#fde7ed] rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#d75078]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <span class="font-bold text-gray-800 text-3xl">{{ $citasHoy }}</span>
            <span class="text-gray-400 text-xs">Total del día</span>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <span class="text-gray-400 text-sm font-medium">Pendientes</span>
                <div class="w-10 h-10 bg-yellow-50 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <span class="font-bold text-gray-800 text-3xl">{{ $citasPendientes }}</span>
            <span class="text-gray-400 text-xs">Por confirmar</span>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <span class="text-gray-400 text-sm font-medium">Atendidas</span>
                <div class="w-10 h-10 bg-green-50 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <span class="font-bold text-gray-800 text-3xl">{{ $citasAtendidas }}</span>
            <span class="text-gray-400 text-xs">Completadas hoy</span>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <span class="text-gray-400 text-sm font-medium">Canceladas</span>
                <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <span class="font-bold text-gray-800 text-3xl">{{ $citasCanceladas }}</span>
            <span class="text-gray-400 text-xs">Canceladas hoy</span>
        </div>

    </div>

    {{-- Calendario de citas del mes --}}
    <div class="bg-white rounded-2xl shadow-sm p-6" x-data="calendario()">

        {{-- Header del calendario --}}
        <div class="flex items-center justify-between mb-6">
            <h2 class="font-bold text-gray-800 text-lg">Calendario de citas</h2>
            <div class="flex items-center gap-3">
                <button @click="mesAnterior()" class="p-2 rounded-lg hover:bg-gray-100 transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <span class="font-medium text-gray-700 text-sm min-w-[140px] text-center capitalize" x-text="nombreMes + ' ' + anio"></span>
                <button @click="mesSiguiente()" class="p-2 rounded-lg hover:bg-gray-100 transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Dias de la semana --}}
        <div class="grid grid-cols-7 gap-2 mb-2">
            <template x-for="dia in ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb']" :key="dia">
                <div class="text-center text-gray-400 text-xs font-medium py-2" x-text="dia"></div>
            </template>
        </div>

        {{-- Cuadricula del calendario --}}
        <div class="grid grid-cols-7 gap-2">
            <template x-for="n in espaciosVacios" :key="'vacio-' + n">
                <div></div>
            </template>

            <template x-for="dia in diasDelMes" :key="dia">
                <div class="min-h-[90px] border border-gray-100 rounded-xl p-2 hover:border-[#f285a2] transition-all duration-200"
                     :class="esHoy(dia) ? 'bg-[#fde7ed] border-[#d75078]' : ''">
                    <span class="text-xs font-medium" :class="esHoy(dia) ? 'text-[#d75078]' : 'text-gray-600'" x-text="dia"></span>

                    <div class="flex flex-col gap-1 mt-1">
                        <template x-for="cita in citasDelDia(dia)" :key="cita.id">
                            <button @click="verCita(cita)"
                                    class="text-left text-[10px] px-1.5 py-0.5 rounded truncate transition-all duration-200 hover:opacity-80"
                                    :class="{
                                        'bg-yellow-50 text-yellow-700': cita.status === 'pendiente',
                                        'bg-blue-50 text-blue-700': cita.status === 'confirmada',
                                        'bg-green-50 text-green-700': cita.status === 'atendida'
                                    }">
                                <span x-text="cita.hora_formato + ' ' + cita.paciente"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Modal de detalle de cita --}}
        <div x-show="citaSeleccionada" x-cloak
             class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
             @click.self="citaSeleccionada = null">
            <div x-show="citaSeleccionada"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="bg-white rounded-2xl shadow-lg w-full max-w-sm p-6">

                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-800 text-lg">Detalle de la cita</h3>
                    <button @click="citaSeleccionada = null" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <template x-if="citaSeleccionada">
                    <div class="flex flex-col gap-3">
                        <div>
                            <span class="text-gray-400 text-xs">Paciente</span>
                            <p class="text-gray-800 text-sm font-medium" x-text="citaSeleccionada.paciente"></p>
                        </div>
                        <div>
                            <span class="text-gray-400 text-xs">Servicio</span>
                            <p class="text-gray-800 text-sm font-medium" x-text="citaSeleccionada.servicio"></p>
                        </div>
                        <div class="flex gap-6">
                            <div>
                                <span class="text-gray-400 text-xs">Fecha</span>
                                <p class="text-gray-800 text-sm font-medium" x-text="citaSeleccionada.fecha"></p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-xs">Hora</span>
                                <p class="text-gray-800 text-sm font-medium" x-text="citaSeleccionada.hora_formato"></p>
                            </div>
                        </div>
                        <div>
                            <span class="text-gray-400 text-xs">Estado</span>
                            <p class="text-sm font-medium capitalize" x-text="citaSeleccionada.status"></p>
                        </div>
                        <a href="{{ route('asistente.citas.index') }}"
                           class="mt-2 bg-[#d75078] text-white text-sm font-medium py-2.5 rounded-xl hover:bg-[#f285a2] transition-all duration-300 text-center">
                            Ir a gestión de citas
                        </a>
                    </div>
                </template>
            </div>
        </div>

    </div>

    <script>
        function calendario() {
            return {
                citas: @json($citasMes),
                fecha: new Date(),
                citaSeleccionada: null,
                meses: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],

                get anio() { return this.fecha.getFullYear(); },
                get mes() { return this.fecha.getMonth(); },
                get nombreMes() { return this.meses[this.mes]; },

                get diasDelMes() {
                    const dias = new Date(this.anio, this.mes + 1, 0).getDate();
                    return Array.from({ length: dias }, (_, i) => i + 1);
                },

                get espaciosVacios() {
                    const primerDia = new Date(this.anio, this.mes, 1).getDay();
                    return Array.from({ length: primerDia }, (_, i) => i + 1);
                },

                citasDelDia(dia) {
                    const fechaStr = `${this.anio}-${String(this.mes + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
                    return this.citas.filter(c => c.fecha === fechaStr);
                },

                esHoy(dia) {
                    const hoy = new Date();
                    return dia === hoy.getDate() && this.mes === hoy.getMonth() && this.anio === hoy.getFullYear();
                },

                mesAnterior() {
                    this.fecha = new Date(this.anio, this.mes - 1, 1);
                },

                mesSiguiente() {
                    this.fecha = new Date(this.anio, this.mes + 1, 1);
                },

                verCita(cita) {
                    this.citaSeleccionada = cita;
                }
            }
        }
    </script>

</x-asistente.layout>