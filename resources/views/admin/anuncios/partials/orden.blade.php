{{-- Posición en el carrusel con botones para subir y bajar (funcionan igual en el celular) --}}
<div class="flex items-center gap-1">
    <form method="POST" action="{{ route('admin.anuncios.subir', $anuncio->id) }}">
        @csrf
        @method('PATCH')
        <button type="submit" @disabled($loop->first) title="Subir" aria-label="Subir"
                class="p-1.5 text-gray-400 hover:text-[#d75078] rounded-lg transition-all duration-200 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
        </button>
    </form>
    <span class="text-gray-600 text-sm font-medium w-5 text-center">{{ $loop->iteration }}</span>
    <form method="POST" action="{{ route('admin.anuncios.bajar', $anuncio->id) }}">
        @csrf
        @method('PATCH')
        <button type="submit" @disabled($loop->last) title="Bajar" aria-label="Bajar"
                class="p-1.5 text-gray-400 hover:text-[#d75078] rounded-lg transition-all duration-200 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
    </form>
</div>
