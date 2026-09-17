{{-- Contraseña temporal: no se desvanece sola, solo se cierra con la X --}}
@if(session('password_temporal'))
    @php($datos = session('password_temporal'))
    <div x-data="{
            visible: true,
            copiado: false,
            copiar() {
                const texto = $refs.password.textContent.trim();
                const listo = () => { this.copiado = true; setTimeout(() => this.copiado = false, 2000); };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(texto).then(listo);
                    return;
                }

                // Respaldo para navegadores sin portapapeles disponible
                const campo = document.createElement('textarea');
                campo.value = texto;
                document.body.appendChild(campo);
                campo.select();
                document.execCommand('copy');
                campo.remove();
                listo();
            }
         }"
         x-show="visible" x-cloak
         class="mb-6 bg-green-50 border border-green-200 rounded-2xl p-5">

        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <p class="text-green-800 text-sm font-bold">Contraseña temporal de {{ $datos['nombre'] }}</p>
                <p class="text-green-700 text-xs mt-0.5 break-all">{{ $datos['email'] }}</p>
            </div>
            <button type="button" @click="visible = false" class="text-green-700 hover:text-green-900 text-lg leading-none shrink-0" aria-label="Cerrar">✕</button>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <p x-ref="password" class="flex-1 bg-white border border-green-200 rounded-xl px-4 py-3 text-gray-800 text-lg font-mono tracking-wider select-all break-all">{{ $datos['password'] }}</p>
            <button type="button" @click="copiar()"
                    class="shrink-0 bg-[#d75078] text-white text-sm font-medium px-5 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                <span x-text="copiado ? 'Copiado' : 'Copiar'">Copiar</span>
            </button>
        </div>

        <p class="text-green-700 text-xs mt-3">
            Anótala y compártela con la persona: no se volverá a mostrar. Si se pierde, puedes generar otra con el botón "Restablecer contraseña".
        </p>
    </div>
@endif
