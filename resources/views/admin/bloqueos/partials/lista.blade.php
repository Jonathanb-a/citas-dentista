{{-- Vista de tabla (desktop) --}}
<div class="hidden md:block overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="border-b border-gray-100">
                <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">PERIODO</th>
                <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">MOTIVO</th>
                <th class="text-right py-3 px-4 text-gray-400 text-xs font-medium">ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bloqueos as $bloqueo)
            <tr class="border-b border-gray-50 hover:bg-[#FFF6F6] transition-colors duration-200">
                <td class="py-4 px-4 text-gray-700 text-sm font-medium">{{ $periodo($bloqueo) }}</td>
                <td class="py-4 px-4 text-gray-500 text-sm">{{ $bloqueo->motivo ?? '—' }}</td>
                <td class="py-4 px-4">
                    <div class="flex justify-end">
                        <form method="POST" action="{{ route('admin.bloqueos.destroy', $bloqueo->id) }}"
                              onsubmit="return confirm('¿Eliminar este bloqueo? Esos horarios volverán a estar disponibles.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 bg-red-50 text-red-600 text-xs font-medium rounded-lg hover:bg-red-100 transition-all duration-200">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Vista de tarjetas (móvil) --}}
<div class="md:hidden flex flex-col gap-4">
    @foreach($bloqueos as $bloqueo)
    <div class="border border-gray-100 rounded-xl p-4 flex flex-col gap-2">
        <p class="text-gray-800 text-sm font-medium">{{ $periodo($bloqueo) }}</p>
        <p class="text-gray-500 text-sm">{{ $bloqueo->motivo ?? 'Sin motivo' }}</p>
        <form method="POST" action="{{ route('admin.bloqueos.destroy', $bloqueo->id) }}"
              onsubmit="return confirm('¿Eliminar este bloqueo? Esos horarios volverán a estar disponibles.');"
              class="pt-2 border-t border-gray-50">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full px-3 py-2 bg-red-50 text-red-600 text-xs font-medium rounded-lg">Eliminar</button>
        </form>
    </div>
    @endforeach
</div>
