<x-admin.layout title="Nuevo anuncio">

    <div class="mb-8">
        <a href="{{ route('admin.anuncios.index') }}" class="text-gray-400 text-sm hover:text-[#d75078] transition-colors duration-300">← Volver a anuncios</a>
        <h1 class="font-bold text-gray-800 text-2xl mt-2">Nuevo anuncio</h1>
    </div>

    <form method="POST" action="{{ route('admin.anuncios.store') }}">
        @csrf
        @include('admin.anuncios.partials.form', ['anuncio' => null])
    </form>

</x-admin.layout>
