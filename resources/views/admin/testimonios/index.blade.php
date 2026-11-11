<x-admin.layout title="Testimonios">

@php
    $pestanas = ['pendiente' => 'Pendientes', 'aprobado' => 'Aprobados', 'oculto' => 'Ocultos'];
    $vacios = [
        'pendiente' => 'No hay testimonios pendientes de revisión.',
        'aprobado' => 'Aún no hay testimonios aprobados. La landing no muestra opiniones de pacientes hasta que apruebes alguno.',
        'oculto' => 'No hay testimonios ocultos.',
    ];
    $nombre = fn ($t) => trim($t->paciente->name . ' ' . $t->paciente->apellidos);
@endphp

    <div class="mb-8">
        <h1 class="font-bold text-gray-800 text-2xl">Testimonios de pacientes</h1>
        <p class="font-light text-gray-500 text-sm mt-1">Lee cada opinión antes de aprobarla. En la página solo se muestra el nombre de pila y la inicial del apellido.</p>
        <p class="text-gray-500 text-xs mt-2">Destacados: <strong class="font-semibold text-gray-700">{{ $destacados }}</strong> de {{ $maxDestacados }} (aparecen primero en la landing).</p>
    </div>

    <nav class="flex gap-2 overflow-x-auto pb-1 mb-6" aria-label="Filtrar testimonios">
        @foreach($pestanas as $estado => $titulo)
            <a href="{{ route('admin.testimonios.index', ['estado' => $estado]) }}"
               @if($filtro === $estado) aria-current="page" @endif
               class="whitespace-nowrap text-sm font-medium px-5 py-2.5 rounded-xl transition-all duration-300 {{ $filtro === $estado ? 'bg-[#d75078] text-white' : 'bg-white text-gray-500 hover:bg-[#fde7ed] hover:text-[#d75078]' }}">
                {{ $titulo }} <span class="ml-1 opacity-80">({{ $conteos[$estado] ?? 0 }})</span>
            </a>
        @endforeach
    </nav>

    <div class="bg-white rounded-2xl shadow-sm p-6">
        @if($testimonios->isEmpty())
            <p class="text-gray-400 text-sm text-center py-12">{{ $vacios[$filtro] }}</p>
        @else
            {{-- Tabla (desktop) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">PACIENTE</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">OPINIÓN</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">FECHA</th>
                            <th class="text-right py-3 px-4 text-gray-400 text-xs font-medium">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($testimonios as $t)
                        <tr class="border-b border-gray-50 hover:bg-[#FFF6F6] transition-colors duration-200 align-top">
                            <td class="py-4 px-4 w-52">
                                <p class="text-gray-700 text-sm font-medium break-words">{{ $nombre($t) }}</p>
                                <p class="text-gray-400 text-xs mt-0.5">Se verá como: {{ $t->nombrePublico() }}</p>
                                @if($t->destacado)<span class="inline-block mt-2 text-xs font-medium bg-[#fde7ed] text-[#a13153] px-2 py-1 rounded-full">★ Destacado</span>@endif
                            </td>
                            <td class="py-4 px-4">
                                @include('admin.testimonios.estrellas', ['calificacion' => $t->calificacion])
                                <p class="text-gray-700 text-sm mt-2 leading-relaxed break-words max-w-xl">{{ $t->texto }}</p>
                            </td>
                            <td class="py-4 px-4 text-gray-500 text-xs whitespace-nowrap">{{ $t->created_at->format('d/m/Y') }}@if($t->updated_at->gt($t->created_at->copy()->addMinute()))<br>Editado {{ $t->updated_at->format('d/m/Y') }}@endif</td>
                            <td class="py-4 px-4">@include('admin.testimonios.acciones')</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Tarjetas (móvil) --}}
            <div class="md:hidden space-y-4">
                @foreach($testimonios as $t)
                    <article class="rounded-2xl border border-gray-100 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-gray-700 text-sm font-medium break-words">{{ $nombre($t) }}</p>
                                <p class="text-gray-400 text-xs mt-0.5">Se verá como: {{ $t->nombrePublico() }}</p>
                            </div>
                            <span class="text-gray-400 text-xs shrink-0">{{ $t->created_at->format('d/m/Y') }}</span>
                        </div>
                        <div class="mt-3 flex items-center gap-2">
                            @include('admin.testimonios.estrellas', ['calificacion' => $t->calificacion])
                            @if($t->destacado)<span class="text-xs font-medium bg-[#fde7ed] text-[#a13153] px-2 py-1 rounded-full">★ Destacado</span>@endif
                        </div>
                        <p class="text-gray-700 text-sm mt-2 leading-relaxed break-words">{{ $t->texto }}</p>
                        <div class="mt-4">@include('admin.testimonios.acciones')</div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6">{{ $testimonios->links() }}</div>
        @endif
    </div>

</x-admin.layout>
