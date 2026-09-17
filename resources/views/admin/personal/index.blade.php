<x-admin.layout title="Personal">

@php
    // Si la validación falló al editar, se reabre el modal de esa persona
    $bagEditar = collect($errors->getBags())->keys()->first(fn ($bag) => str_starts_with($bag, 'editar_'));
    $idEditando = $bagEditar ? (int) substr($bagEditar, strlen('editar_')) : null;
    $usuarioEditando = $idEditando ? $personal->firstWhere('id', $idEditando) : null;

    $editarInicial = $usuarioEditando ? [
        'id' => $usuarioEditando->id,
        'name' => old('name', $usuarioEditando->name),
        'apellidos' => old('apellidos', $usuarioEditando->apellidos),
        'email' => old('email', $usuarioEditando->email),
        'telefono' => old('telefono', $usuarioEditando->telefono),
        'sexo' => old('sexo', $usuarioEditando->sexo),
        'role' => old('role', $usuarioEditando->role),
        'esYo' => $usuarioEditando->id === auth()->id(),
    ] : null;

    $claseInput = 'w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300';
@endphp

<div x-data="{
        modalCrear: {{ $errors->crear->any() ? 'true' : 'false' }},
        editando: {{ Illuminate\Support\Js::from($editarInicial) }},
        editar(datos) { this.editando = datos; }
     }">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="font-bold text-gray-800 text-2xl">Personal</h1>
            <p class="font-light text-gray-400 text-sm mt-1">Administra las cuentas que operan el sistema</p>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-gray-400 text-sm">{{ $personal->total() }} {{ $personal->total() === 1 ? 'cuenta' : 'cuentas' }}</span>
            <button @click="modalCrear = true"
                    class="bg-[#d75078] text-white text-sm font-medium px-5 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                Agregar personal
            </button>
        </div>
    </div>

    {{-- Buscador --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('admin.personal.index') }}" class="flex flex-col md:flex-row gap-4 md:items-end">
            <div class="flex flex-col gap-1.5 flex-1">
                <label class="text-gray-600 text-sm font-medium">Buscar</label>
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                       placeholder="Nombre, apellidos, correo o teléfono"
                       class="{{ $claseInput }}">
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="flex-1 md:flex-none bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                    Buscar
                </button>
                <a href="{{ route('admin.personal.index') }}"
                   class="flex-1 md:flex-none text-center border border-gray-200 text-gray-500 text-sm font-medium px-6 py-3 rounded-xl hover:bg-gray-50 transition-all duration-300">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- Listado --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        @if($personal->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-gray-400 text-sm">
                    {{ request()->filled('buscar') ? 'No hay cuentas que coincidan con la búsqueda' : 'No hay personal registrado todavía' }}
                </p>
            </div>
        @else
            {{-- Vista de tabla (desktop) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">PERSONA</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">CONTACTO</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">ROL</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">ESTADO</th>
                            <th class="text-right py-3 px-4 text-gray-400 text-xs font-medium">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($personal as $usuario)
                        <tr class="border-b border-gray-50 hover:bg-[#FFF6F6] transition-colors duration-200">
                            <td class="py-4 px-4">
                                <p class="text-gray-700 text-sm font-medium">
                                    {{ $usuario->name }} {{ $usuario->apellidos }}
                                    @if($usuario->id === auth()->id())
                                        <span class="text-gray-400 text-xs font-normal">(tú)</span>
                                    @endif
                                </p>
                                <p class="text-gray-400 text-xs">Desde el {{ $usuario->created_at?->format('d/m/Y') }}</p>
                            </td>
                            <td class="py-4 px-4">
                                <p class="text-gray-700 text-sm break-all">{{ $usuario->email }}</p>
                                <p class="text-gray-400 text-xs">{{ $usuario->telefono ?? 'Sin teléfono' }}</p>
                            </td>
                            <td class="py-4 px-4">
                                @include('admin.personal.partials.rol', ['role' => $usuario->role])
                            </td>
                            <td class="py-4 px-4">
                                @if($usuario->activo)
                                    <span class="bg-green-50 text-green-600 text-xs font-medium px-3 py-1 rounded-full">Activa</span>
                                @else
                                    <span class="bg-red-50 text-red-600 text-xs font-medium px-3 py-1 rounded-full">Inactiva</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center justify-end gap-2">
                                    @include('admin.personal.partials.acciones', ['usuario' => $usuario, 'clases' => 'px-3 py-1.5 text-xs font-medium rounded-lg'])
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Vista de tarjetas (móvil) --}}
            <div class="md:hidden flex flex-col gap-4">
                @foreach($personal as $usuario)
                <div class="border border-gray-100 rounded-xl p-4 flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-gray-800 text-sm font-medium">
                                {{ $usuario->name }} {{ $usuario->apellidos }}
                                @if($usuario->id === auth()->id())
                                    <span class="text-gray-400 text-xs font-normal">(tú)</span>
                                @endif
                            </p>
                            <p class="text-gray-400 text-xs break-all">{{ $usuario->email }}</p>
                        </div>
                        @if($usuario->activo)
                            <span class="bg-green-50 text-green-600 text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap">Activa</span>
                        @else
                            <span class="bg-red-50 text-red-600 text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap">Inactiva</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        @include('admin.personal.partials.rol', ['role' => $usuario->role])
                        <span class="text-gray-600 text-sm">{{ $usuario->telefono ?? 'Sin teléfono' }}</span>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-50">
                        @include('admin.personal.partials.acciones', ['usuario' => $usuario, 'clases' => 'w-full px-3 py-2 text-xs font-medium rounded-lg', 'contenedor' => 'flex-1 min-w-[90px]'])
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Paginación --}}
            @if($personal->hasPages())
                <div class="flex items-center justify-between gap-4 pt-6 mt-2 border-t border-gray-50">
                    @if($personal->onFirstPage())
                        <span class="px-4 py-2 border border-gray-100 text-gray-300 text-sm font-medium rounded-xl cursor-not-allowed">Anterior</span>
                    @else
                        <a href="{{ $personal->previousPageUrl() }}"
                           class="px-4 py-2 border border-gray-200 text-gray-500 text-sm font-medium rounded-xl hover:bg-[#fde7ed] hover:text-[#d75078] transition-all duration-300">Anterior</a>
                    @endif

                    <span class="text-gray-400 text-sm">Página {{ $personal->currentPage() }} de {{ $personal->lastPage() }}</span>

                    @if($personal->hasMorePages())
                        <a href="{{ $personal->nextPageUrl() }}"
                           class="px-4 py-2 border border-gray-200 text-gray-500 text-sm font-medium rounded-xl hover:bg-[#fde7ed] hover:text-[#d75078] transition-all duration-300">Siguiente</a>
                    @else
                        <span class="px-4 py-2 border border-gray-100 text-gray-300 text-sm font-medium rounded-xl cursor-not-allowed">Siguiente</span>
                    @endif
                </div>
            @endif
        @endif
    </div>

    {{-- Modal: agregar personal --}}
    <div x-show="modalCrear" x-cloak
         class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
         @click.self="modalCrear = false">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-bold text-gray-800 text-lg">Agregar personal</h2>
                <button @click="modalCrear = false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            @if($errors->crear->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">
                    <ul class="list-disc list-inside">
                        @foreach($errors->crear->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.personal.store') }}" class="flex flex-col gap-4">
                @csrf

                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Nombre</label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="{{ $claseInput }}">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Apellidos</label>
                    <input type="text" name="apellidos" value="{{ old('apellidos') }}" required maxlength="255" class="{{ $claseInput }}">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Correo electrónico</label>
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" class="{{ $claseInput }}">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Teléfono</label>
                    <input type="tel" name="telefono" value="{{ old('telefono') }}" maxlength="20" placeholder="Ej. 7531234567" class="{{ $claseInput }}">
                </div>
                <div class="flex flex-col gap-1.5">
                    <span class="text-gray-600 text-sm font-medium">Sexo</span>
                    @include('partials.selector-sexo', ['nombre' => 'sexo', 'valor' => old('sexo')])
                </div>
                <div class="flex flex-col gap-1.5">
                    <span class="text-gray-600 text-sm font-medium">Rol</span>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach(['admin' => 'Doctora', 'asistente' => 'Asistente'] as $valor => $etiqueta)
                            <label class="cursor-pointer">
                                <input type="radio" name="role" value="{{ $valor }}" class="peer sr-only" required
                                       @checked(old('role', 'asistente') === $valor)>
                                <span class="block text-center py-3 rounded-xl border border-gray-200 text-gray-500 text-sm font-medium transition-all duration-300 hover:bg-[#FFF6F6] peer-checked:border-[#d75078] peer-checked:bg-[#fde7ed] peer-checked:text-[#d75078]">
                                    {{ $etiqueta }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <p class="text-gray-400 text-xs">Se generará una contraseña temporal que se mostrará al guardar.</p>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="modalCrear = false"
                            class="flex-1 border border-gray-200 text-gray-500 text-sm font-medium py-3 rounded-xl hover:bg-gray-50 transition-all duration-300">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 bg-[#d75078] text-white text-sm font-medium py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                        Crear cuenta
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: editar personal --}}
    <div x-show="editando" x-cloak
         class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
         @click.self="editando = null">
        {{-- El template evita que Alpine lea 'editando' cuando no hay nadie seleccionado --}}
        <template x-if="editando">
        <div class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-bold text-gray-800 text-lg">Editar personal</h2>
                <button @click="editando = null" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            @if($bagEditar && $errors->{$bagEditar}->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">
                    <ul class="list-disc list-inside">
                        @foreach($errors->{$bagEditar}->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" :action="'{{ url('admin/personal') }}/' + editando.id" class="flex flex-col gap-4">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Nombre</label>
                    <input type="text" name="name" x-model="editando.name" required maxlength="255" class="{{ $claseInput }}">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Apellidos</label>
                    <input type="text" name="apellidos" x-model="editando.apellidos" required maxlength="255" class="{{ $claseInput }}">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Correo electrónico</label>
                    <input type="email" name="email" x-model="editando.email" required maxlength="255" class="{{ $claseInput }}">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Teléfono</label>
                    <input type="tel" name="telefono" x-model="editando.telefono" maxlength="20" class="{{ $claseInput }}">
                </div>
                <div class="flex flex-col gap-1.5">
                    <span class="text-gray-600 text-sm font-medium">Sexo</span>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach(['masculino' => 'Masculino', 'femenino' => 'Femenino'] as $valor => $etiqueta)
                            <label class="cursor-pointer">
                                <input type="radio" name="sexo" value="{{ $valor }}" class="peer sr-only" x-model="editando.sexo">
                                <span class="block text-center py-3 rounded-xl border border-gray-200 text-gray-500 text-sm font-medium transition-all duration-300 hover:bg-[#FFF6F6] peer-checked:border-[#d75078] peer-checked:bg-[#fde7ed] peer-checked:text-[#d75078]">
                                    {{ $etiqueta }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <span class="text-gray-600 text-sm font-medium">Rol</span>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach(['admin' => 'Doctora', 'asistente' => 'Asistente'] as $valor => $etiqueta)
                            <label class="cursor-pointer" :class="editando.esYo ? 'cursor-not-allowed opacity-60' : ''">
                                <input type="radio" name="role" value="{{ $valor }}" class="peer sr-only" x-model="editando.role"
                                       :disabled="editando.esYo">
                                <span class="block text-center py-3 rounded-xl border border-gray-200 text-gray-500 text-sm font-medium transition-all duration-300 peer-checked:border-[#d75078] peer-checked:bg-[#fde7ed] peer-checked:text-[#d75078]">
                                    {{ $etiqueta }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <span x-show="editando.esYo" class="text-gray-400 text-xs">No puedes cambiar tu propio rol.</span>
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="editando = null"
                            class="flex-1 border border-gray-200 text-gray-500 text-sm font-medium py-3 rounded-xl hover:bg-gray-50 transition-all duration-300">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 bg-[#d75078] text-white text-sm font-medium py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
        </template>
    </div>

</div>

</x-admin.layout>
