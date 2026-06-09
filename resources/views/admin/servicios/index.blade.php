<x-admin.layout title="Servicios">

    <div x-data="{
        modalAbierto: false,
        modoEdicion: false,
        form: { id: null, especialidad_id: '', nombre: '', descripcion: '', duracion_minutos: '' },
        abrirCrear() {
            this.modoEdicion = false;
            this.form = { id: null, especialidad_id: '', nombre: '', descripcion: '', duracion_minutos: '' };
            this.modalAbierto = true;
        },
        abrirEditar(serv) {
            this.modoEdicion = true;
            this.form = {
                id: serv.id,
                especialidad_id: serv.especialidad_id,
                nombre: serv.nombre,
                descripcion: serv.descripcion || '',
                duracion_minutos: serv.duracion_minutos
            };
            this.modalAbierto = true;
        }
    }">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="font-bold text-gray-800 text-2xl">Servicios</h1>
                <p class="font-light text-gray-400 text-sm mt-1">Gestiona los servicios de la clínica</p>
            </div>
            <button @click="abrirCrear()"
                    class="bg-[#d75078] text-white text-sm font-medium px-5 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo servicio
            </button>
        </div>

        {{-- Aviso si no hay especialidades --}}
        @if($especialidades->isEmpty())
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 mb-6 text-yellow-700 text-sm">
                ⚠️ Primero debes crear al menos una especialidad activa antes de agregar servicios.
            </div>
        @endif

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            @if($servicios->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 gap-3">
                    <p class="text-gray-400 text-sm">No hay servicios registrados todavía</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">SERVICIO</th>
                                <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">ESPECIALIDAD</th>
                                <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">DURACIÓN</th>
                                <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">ESTADO</th>
                                <th class="text-right py-3 px-4 text-gray-400 text-xs font-medium">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($servicios as $serv)
                            <tr class="border-b border-gray-50 hover:bg-[#FFF6F6] transition-colors duration-200">
                                <td class="py-4 px-4 text-gray-700 text-sm font-medium">{{ $serv->nombre }}</td>
                                <td class="py-4 px-4 text-gray-500 text-sm">{{ $serv->especialidad->nombre ?? '—' }}</td>
                                <td class="py-4 px-4 text-gray-500 text-sm">{{ $serv->duracion_minutos }} min</td>
                                <td class="py-4 px-4">
                                    @if($serv->activo)
                                        <span class="bg-green-50 text-green-600 text-xs font-medium px-3 py-1 rounded-full">Activo</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-500 text-xs font-medium px-3 py-1 rounded-full">Inactivo</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Editar --}}
                                        <button @click='abrirEditar(@json($serv))'
                                                class="p-2 text-gray-400 hover:text-[#d75078] hover:bg-[#fde7ed] rounded-lg transition-all duration-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>

                                        {{-- Toggle --}}
                                        <form method="POST" action="{{ route('admin.servicios.toggle', $serv->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="p-2 text-gray-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-all duration-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </button>
                                        </form>

                                        {{-- Eliminar --}}
                                        <form method="POST" action="{{ route('admin.servicios.destroy', $serv->id) }}"
                                              onsubmit="return confirm('¿Seguro que deseas eliminar este servicio?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all duration-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Modal Crear/Editar --}}
        <div x-show="modalAbierto" x-cloak
             class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
             @click.self="modalAbierto = false">

            <div x-show="modalAbierto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6">

                <h2 class="font-bold text-gray-800 text-lg mb-6" x-text="modoEdicion ? 'Editar servicio' : 'Nuevo servicio'"></h2>

                <form :action="modoEdicion ? '/admin/servicios/' + form.id : '/admin/servicios'" method="POST" class="flex flex-col gap-4">
                    @csrf
                    <template x-if="modoEdicion">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    {{-- Especialidad --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-gray-600 text-sm font-medium">Especialidad</label>
                        <select name="especialidad_id" x-model="form.especialidad_id"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                            <option value="">Selecciona una especialidad</option>
                            @foreach($especialidades as $esp)
                                <option value="{{ $esp->id }}">{{ $esp->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Nombre --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-gray-600 text-sm font-medium">Nombre del servicio</label>
                        <input type="text" name="nombre" x-model="form.nombre"
                               placeholder="Ej. Limpieza dental"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                    </div>

                    {{-- Duracion --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-gray-600 text-sm font-medium">Duración (minutos)</label>
                        <input type="number" name="duracion_minutos" x-model="form.duracion_minutos"
                               placeholder="Ej. 45" min="5" max="480"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                    </div>

                    {{-- Descripcion --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-gray-600 text-sm font-medium">Descripción (opcional)</label>
                        <textarea name="descripcion" x-model="form.descripcion" rows="2"
                                  placeholder="Breve descripción del servicio"
                                  class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300 resize-none"></textarea>
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