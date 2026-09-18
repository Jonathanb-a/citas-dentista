{{-- Modal de nota de evolución: sirve para atender una cita y para editar la nota después --}}
<div x-show="nota" x-cloak
     class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
     @click.self="nota = null" @keydown.escape.window="nota = null">
    <template x-if="nota">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">

            <div class="flex items-start justify-between gap-3 mb-5">
                <div>
                    <h2 class="font-bold text-gray-800 text-lg"
                        x-text="nota.accion === 'atender' ? 'Atender y registrar evolución' : 'Nota de evolución'"></h2>
                    <p class="text-gray-400 text-xs mt-0.5">
                        <span x-text="nota.paciente"></span> · <span x-text="nota.fecha"></span> · <span x-text="nota.servicio"></span>
                    </p>
                </div>
                <button type="button" @click="nota = null" class="text-gray-400 hover:text-gray-600 shrink-0">✕</button>
            </div>

            <template x-if="nota.errores.length">
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">
                    <ul class="list-disc list-inside">
                        <template x-for="error in nota.errores" :key="error">
                            <li x-text="error"></li>
                        </template>
                    </ul>
                </div>
            </template>

            {{-- Formulario principal: guarda la nota --}}
            <form method="POST" :action="nota.accion === 'atender'
                    ? '{{ url('admin/citas') }}/' + nota.citaId + '/atender'
                    : '{{ url('admin/citas') }}/' + nota.citaId + '/nota'">
                @csrf
                <input type="hidden" name="_method" :value="nota.accion === 'atender' ? 'PATCH' : 'PUT'">

                @include('admin.citas.partials.campos-nota')

                <p class="text-gray-400 text-xs mt-3" x-show="nota.accion === 'editar'">
                    Si dejas todos los campos vacíos, la nota se elimina.
                </p>

                <div class="flex flex-col sm:flex-row gap-3 mt-5">
                    <button type="button" @click="nota = null"
                            class="flex-1 border border-gray-200 text-gray-500 text-sm font-medium py-3 rounded-xl hover:bg-gray-50 transition-all duration-300">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 bg-[#d75078] text-white text-sm font-medium py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300"
                            x-text="nota.accion === 'atender' ? 'Guardar y marcar atendida' : 'Guardar nota'"></button>
                </div>
            </form>

            {{-- Saltar la nota: marca atendida sin registrar nada --}}
            <form method="POST" :action="'{{ url('admin/citas') }}/' + nota.citaId + '/atender'"
                  x-show="nota.accion === 'atender'" class="mt-3">
                @csrf
                @method('PATCH')
                <button type="submit"
                        class="w-full border border-gray-200 text-gray-500 text-sm font-medium py-3 rounded-xl hover:bg-gray-50 transition-all duration-300">
                    Solo marcar atendida
                </button>
                <p class="text-gray-400 text-xs mt-2 text-center">Podrás agregar la nota después desde esta misma lista.</p>
            </form>
        </div>
    </template>
</div>
