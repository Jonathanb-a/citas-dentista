{{-- Nota de evolución en solo lectura: la asistente puede consultarla, no escribirla --}}
<div x-show="notaVista" x-cloak
     class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
     @click.self="notaVista = null" @keydown.escape.window="notaVista = null">
    <template x-if="notaVista">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">

            <div class="flex items-start justify-between gap-3 mb-5">
                <div>
                    <h2 class="font-bold text-gray-800 text-lg">Nota de evolución</h2>
                    <p class="text-gray-400 text-xs mt-0.5">
                        <span x-text="notaVista.paciente"></span> · <span x-text="notaVista.fecha"></span> · <span x-text="notaVista.servicio"></span>
                    </p>
                </div>
                <button type="button" @click="notaVista = null" class="text-gray-400 hover:text-gray-600 shrink-0">✕</button>
            </div>

            <dl class="flex flex-col gap-4">
                <template x-for="campo in notaVista.campos" :key="campo.etiqueta">
                    <div x-show="campo.valor">
                        <dt class="text-gray-400 text-xs font-medium" x-text="campo.etiqueta"></dt>
                        <dd class="text-gray-700 text-sm mt-1 whitespace-pre-line" x-text="campo.valor"></dd>
                    </div>
                </template>
            </dl>

            <p class="text-gray-400 text-xs mt-5 pt-4 border-t border-gray-50">
                Escrita por <span x-text="notaVista.autor"></span> el <span x-text="notaVista.creada"></span>.
                Solo la doctora puede modificarla.
            </p>

            <button type="button" @click="notaVista = null"
                    class="w-full mt-4 border border-gray-200 text-gray-500 text-sm font-medium py-3 rounded-xl hover:bg-gray-50 transition-all duration-300">
                Cerrar
            </button>
        </div>
    </template>
</div>
