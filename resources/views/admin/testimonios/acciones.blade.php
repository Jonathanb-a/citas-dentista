{{-- Acciones de un testimonio ($t). Las usan la tabla y las tarjetas móviles --}}
@php
    $boton = 'min-h-11 md:min-h-0 text-xs font-medium px-3 py-2 rounded-lg transition-all duration-300';
@endphp
<div class="flex flex-wrap items-center gap-2 md:justify-end">
    @if($t->estado === 'pendiente')
        <form method="POST" action="{{ route('admin.testimonios.aprobar', $t->id) }}">@csrf @method('PATCH')
            <button type="submit" class="{{ $boton }} bg-green-50 text-green-700 hover:bg-green-100">Aprobar</button>
        </form>
    @endif

    @if($t->estado === 'aprobado')
        <form method="POST" action="{{ route('admin.testimonios.destacar', $t->id) }}">@csrf @method('PATCH')
            @if($t->destacado)
                <input type="hidden" name="quitar" value="1">
                <button type="submit" class="{{ $boton }} bg-[#fde7ed] text-[#a13153] hover:bg-[#fbd3df]" aria-pressed="true">★ Destacado</button>
            @else
                <button type="submit" class="{{ $boton }} bg-gray-100 text-gray-600 hover:bg-gray-200" aria-pressed="false">☆ Destacar</button>
            @endif
        </form>
    @endif

    @if($t->estado !== 'oculto')
        <form method="POST" action="{{ route('admin.testimonios.ocultar', $t->id) }}">@csrf @method('PATCH')
            <button type="submit" class="{{ $boton }} bg-yellow-50 text-yellow-700 hover:bg-yellow-100">Ocultar</button>
        </form>
    @else
        <form method="POST" action="{{ route('admin.testimonios.restaurar', $t->id) }}">@csrf @method('PATCH')
            <button type="submit" class="{{ $boton }} bg-blue-50 text-blue-700 hover:bg-blue-100">Restaurar</button>
        </form>
    @endif

    <form method="POST" action="{{ route('admin.testimonios.destroy', $t->id) }}"
          onsubmit="return confirm('¿Eliminar este testimonio? Esta acción no se puede deshacer.')">@csrf @method('DELETE')
        <button type="submit" class="{{ $boton }} text-red-600 hover:bg-red-50">Eliminar</button>
    </form>
</div>
