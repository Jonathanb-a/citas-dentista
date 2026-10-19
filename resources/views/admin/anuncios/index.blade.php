<x-admin.layout title="Landing Page">

@php
    $etiquetas = [
        'vigente' => ['Vigente', 'bg-green-50 text-green-600'],
        'programado' => ['Programado', 'bg-blue-50 text-blue-600'],
        'vencido' => ['Vencido', 'bg-gray-100 text-gray-500'],
        'inactivo' => ['Inactivo', 'bg-yellow-50 text-yellow-600'],
    ];

    $vigencia = function ($anuncio) {
        if (!$anuncio->fecha_inicio && !$anuncio->fecha_fin) {
            return 'Sin fechas';
        }
        if ($anuncio->fecha_inicio && $anuncio->fecha_fin) {
            return $anuncio->fecha_inicio->format('d/m/Y') . ' al ' . $anuncio->fecha_fin->format('d/m/Y');
        }

        return $anuncio->fecha_inicio
            ? 'Desde el ' . $anuncio->fecha_inicio->format('d/m/Y')
            : 'Hasta el ' . $anuncio->fecha_fin->format('d/m/Y');
    };
@endphp

    @include('admin.landing.partials.pestanas', ['activa' => 'anuncios'])

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="font-bold text-gray-800 text-2xl">Landing Page · Anuncios y promociones</h1>
            <p class="font-light text-gray-400 text-sm mt-1">Todos los vigentes salen en un carrusel de la página, en el orden de esta lista</p>
        </div>
        <a href="{{ route('admin.anuncios.create') }}"
           class="bg-[#d75078] text-white text-sm font-medium px-5 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300 flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo anuncio
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6">
        @if($anuncios->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 gap-3">
                <p class="text-gray-400 text-sm text-center">Aún no hay anuncios. La landing se ve igual que siempre hasta que publiques uno.</p>
            </div>
        @else
            {{-- Vista de tabla (desktop) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">ORDEN</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">ANUNCIO</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">TIPO</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">VIGENCIA</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">ESTADO</th>
                            <th class="text-right py-3 px-4 text-gray-400 text-xs font-medium">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($anuncios as $anuncio)
                        @php $estado = $anuncio->estado($hoy); @endphp
                        <tr class="border-b border-gray-50 hover:bg-[#FFF6F6] transition-colors duration-200">
                            <td class="py-4 px-4">
                                @include('admin.anuncios.partials.orden')
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    @if($anuncio->imagenUrl())
                                        <img src="{{ $anuncio->imagenUrl() }}" alt="" class="w-16 h-10 object-cover rounded-lg border border-gray-100 shrink-0">
                                    @endif
                                    <div class="min-w-0">
                                        <span class="text-gray-700 text-sm font-medium">{{ $anuncio->emoji }} {{ $anuncio->titulo }}</span>
                                        @if($anuncio->descripcion)
                                            <p class="text-gray-400 text-xs mt-0.5 max-w-md truncate">{{ $anuncio->descripcion }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-gray-500 text-sm">{{ \App\Models\Anuncio::TIPOS[$anuncio->tipo] ?? $anuncio->tipo }}</td>
                            <td class="py-4 px-4 text-gray-500 text-sm">{{ $vigencia($anuncio) }}</td>
                            <td class="py-4 px-4">
                                <span class="{{ $etiquetas[$estado][1] }} text-xs font-medium px-3 py-1 rounded-full">{{ $etiquetas[$estado][0] }}</span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.anuncios.edit', $anuncio->id) }}"
                                       class="px-3 py-1.5 bg-[#fde7ed] text-[#d75078] text-xs font-medium rounded-lg hover:bg-[#fbd3df] transition-all duration-200">Editar</a>
                                    <form method="POST" action="{{ route('admin.anuncios.toggle', $anuncio->id) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-3 py-1.5 bg-blue-50 text-blue-600 text-xs font-medium rounded-lg hover:bg-blue-100 transition-all duration-200">
                                            {{ $anuncio->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.anuncios.destroy', $anuncio->id) }}" onsubmit="return confirm('¿Eliminar este anuncio?');">
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
                @foreach($anuncios as $anuncio)
                @php $estado = $anuncio->estado($hoy); @endphp
                <div class="border border-gray-100 rounded-xl p-4 flex flex-col gap-3">
                    @if($anuncio->imagenUrl())
                        <img src="{{ $anuncio->imagenUrl() }}" alt="" class="w-full h-32 object-cover rounded-lg">
                    @endif
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-gray-800 text-sm font-medium">{{ $anuncio->emoji }} {{ $anuncio->titulo }}</p>
                        <span class="{{ $etiquetas[$estado][1] }} text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap">{{ $etiquetas[$estado][0] }}</span>
                    </div>
                    @if($anuncio->descripcion)
                        <p class="text-gray-500 text-sm">{{ $anuncio->descripcion }}</p>
                    @endif
                    <p class="text-gray-400 text-xs">{{ \App\Models\Anuncio::TIPOS[$anuncio->tipo] ?? $anuncio->tipo }} · {{ $vigencia($anuncio) }}</p>
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-50">
                        <span class="text-gray-400 text-xs">Posición</span>
                        @include('admin.anuncios.partials.orden')
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.anuncios.edit', $anuncio->id) }}"
                           class="flex-1 min-w-[90px] text-center px-3 py-2 bg-[#fde7ed] text-[#d75078] text-xs font-medium rounded-lg">Editar</a>
                        <form method="POST" action="{{ route('admin.anuncios.toggle', $anuncio->id) }}" class="flex-1 min-w-[90px]">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-full px-3 py-2 bg-blue-50 text-blue-600 text-xs font-medium rounded-lg">
                                {{ $anuncio->activo ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.anuncios.destroy', $anuncio->id) }}" onsubmit="return confirm('¿Eliminar este anuncio?');" class="flex-1 min-w-[90px]">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full px-3 py-2 bg-red-50 text-red-600 text-xs font-medium rounded-lg">Eliminar</button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

</x-admin.layout>
