<x-admin.layout title="Landing Page">

@php
    $total = count($items);
    $lleno = $total >= $definicion['maximo'];
    $publicadas = collect($items)->where('autorizado', true)->count();
    $estiloCampo = 'w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300';
@endphp

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="font-bold text-gray-800 text-2xl">Landing Page · Opiniones</h1>
        <p class="font-light text-gray-400 text-sm mt-1">
            Opiniones reales de pacientes. Solo se publican las que tienen la autorización del paciente; sin ninguna, la sección no aparece en la landing.
        </p>
    </div>

    @include('admin.landing.partials.pestanas', ['activa' => 'testimonios'])

    <div class="flex flex-col gap-6">

        {{-- Lista --}}
        <section id="opiniones" class="bg-white rounded-2xl shadow-sm p-6 scroll-mt-6">

            <div class="flex flex-wrap items-center gap-3 mb-6">
                <h2 class="font-bold text-gray-800 text-lg">{{ $definicion['titulo'] }}</h2>
                <span class="bg-gray-100 text-gray-500 text-xs font-medium px-3 py-1 rounded-full">{{ $total }} de {{ $definicion['maximo'] }}</span>
                <span class="bg-[#fde7ed] text-[#d75078] text-xs font-medium px-3 py-1 rounded-full">{{ $publicadas }} {{ $publicadas === 1 ? 'publicada' : 'publicadas' }}</span>
            </div>

            @if($total === 0)
                <p class="text-gray-500 text-sm">Todavía no hay opiniones. La sección de opiniones no se muestra en la landing.</p>
            @else
                <div class="flex flex-col gap-4">
                    @foreach($items as $item)
                        @include('admin.landing.partials.testimonio-item')
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Agregar una opinión --}}
        @php
            $bolsa = 'nueva';
            $conErrores = $errors->hasBag($bolsa);
            $valoresNueva = $conErrores
                ? collect(array_keys($definicion['campos']))->mapWithKeys(fn ($campo) => [$campo => old($campo)])->all()
                : [];
        @endphp
        <section id="agregar" class="bg-white rounded-2xl shadow-sm p-6 scroll-mt-6">
            <h2 class="font-bold text-gray-800 text-lg mb-1">Agregar una opinión</h2>

            @if($lleno)
                <p class="text-gray-500 text-sm">Ya hay el máximo de {{ $definicion['maximo'] }} opiniones. Quita alguna para agregar otra.</p>
            @else
                <p class="text-gray-400 text-xs mb-5">Copia la opinión tal como la dio el paciente. Se agrega al final; después la puedes subir o bajar de lugar.</p>

                <form method="POST" action="{{ route('admin.landing.testimonios.agregar') }}" class="flex flex-col gap-4">
                    @csrf

                    @include('admin.landing.partials.testimonio-campos', ['prefijo' => 'nueva', 'valores' => $valoresNueva, 'errores' => $errors->getBag($bolsa)])

                    <div>
                        <button type="submit"
                                class="bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                            Agregar opinión
                        </button>
                    </div>
                </form>
            @endif
        </section>

    </div>

</x-admin.layout>
