<x-admin.layout title="Horarios">

    <div x-data="{ modalAbierto: false }">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="font-bold text-gray-800 text-2xl">Horarios de atención</h1>
                <p class="font-light text-gray-400 text-sm mt-1">Define los días y horas en que la doctora atiende</p>
            </div>
            <button @click="modalAbierto = true"
                    class="bg-[#d75078] text-white text-sm font-medium px-5 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Agregar horario
            </button>
        </div>

        {{-- Grid de dias --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            @foreach($dias as $dia)
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="font-bold text-gray-800 text-base mb-4 capitalize">{{ $dia }}</h3>

                @if(isset($horarios[$dia]) && $horarios[$dia]->count() > 0)
                    <div class="flex flex-col gap-3">
                        @foreach($horarios[$dia] as $horario)
                        <div class="flex items-center justify-between p-3 rounded-xl {{ $horario->disponible ? 'bg-[#fde7ed]' : 'bg-gray-50' }}">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 {{ $horario->disponible ? 'text-[#d75078]' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm font-medium {{ $horario->disponible ? 'text-gray-700' : 'text-gray-400 line-through' }}">
                                    {{ \Carbon\Carbon::parse($horario->hora_inicio)->format('h:i A') }} - {{ \Carbon\Carbon::parse($horario->hora_fin)->format('h:i A') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                {{-- Toggle --}}
                                <form method="POST" action="{{ route('admin.horarios.toggle', $horario->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="p-1.5 text-gray-400 hover:text-blue-500 rounded-lg transition-all duration-200" title="Activar/Desactivar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </button>
                                </form>
                                {{-- Eliminar --}}
                                <form method="POST" action="{{ route('admin.horarios.destroy', $horario->id) }}"
                                      onsubmit="return confirm('¿Eliminar este horario?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg transition-all duration-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-300 text-sm">Sin horarios</p>
                @endif
            </div>
            @endforeach

        </div>

        {{-- Modal Agregar --}}
        <div x-show="modalAbierto" x-cloak
             class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
             @click.self="modalAbierto = false">

            <div x-show="modalAbierto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6">

                <h2 class="font-bold text-gray-800 text-lg mb-6">Agregar horario</h2>

                <form action="{{ route('admin.horarios.store') }}" method="POST" class="flex flex-col gap-4">
                    @csrf

                    {{-- Dia --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-gray-600 text-sm font-medium">Día de la semana</label>
                        <select name="dia_semana"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                            <option value="">Selecciona un día</option>
                            <option value="lunes">Lunes</option>
                            <option value="martes">Martes</option>
                            <option value="miercoles">Miércoles</option>
                            <option value="jueves">Jueves</option>
                            <option value="viernes">Viernes</option>
                            <option value="sabado">Sábado</option>
                            <option value="domingo">Domingo</option>
                        </select>
                    </div>

                    {{-- Horas --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-gray-600 text-sm font-medium">Hora inicio</label>
                            <input type="time" name="hora_inicio"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-gray-600 text-sm font-medium">Hora fin</label>
                            <input type="time" name="hora_fin"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                        </div>
                    </div>

                    {{-- Botones --}}
                    <div class="flex gap-3 mt-2">
                        <button type="button" @click="modalAbierto = false"
                                class="flex-1 border border-gray-200 text-gray-500 text-sm font-medium py-3 rounded-xl hover:bg-gray-50 transition-all duration-300">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="flex-1 bg-[#d75078] text-white text-sm font-medium py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</x-admin.layout>