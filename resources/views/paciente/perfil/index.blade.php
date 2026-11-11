<x-paciente.layout title="Mi perfil">

@php
    // La pestaña inicial depende de dónde falló la validación
    $pestanaInicial = $errors->password->any() ? 'seguridad' : 'datos';

    $claseInput = 'w-full px-4 py-3 rounded-xl border text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300';
@endphp

<div x-data="{ pestana: '{{ $pestanaInicial }}' }">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="font-bold text-gray-800 text-2xl">Mi perfil</h1>
        <p class="font-light text-gray-400 text-sm mt-1">Tus datos y tu contraseña</p>
    </div>

    {{-- Pestañas --}}
    <div class="flex gap-2 overflow-x-auto pb-2 mb-6">
        @foreach([
            'datos' => 'Mis datos',
            'seguridad' => 'Seguridad',
        ] as $clave => $titulo)
            <button type="button" @click="pestana = '{{ $clave }}'"
                    :class="pestana === '{{ $clave }}' ? 'bg-[#fde7ed] text-[#d75078]' : 'text-gray-400 hover:text-[#d75078] hover:bg-[#FFF6F6]'"
                    class="relative whitespace-nowrap px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200">
                {{ $titulo }}
                @if(($clave === 'datos' && $errors->datos->any()) || ($clave === 'seguridad' && $errors->password->any()))
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Mis datos --}}
    <div x-show="pestana === 'datos'" class="bg-white rounded-2xl shadow-sm p-6">
        <h2 class="font-bold text-gray-800 text-lg mb-1">Mis datos</h2>
        <p class="text-gray-400 text-sm mb-5">Mantén tu contacto al día para que podamos avisarte de tus citas</p>

        {{-- Foto de perfil --}}
        <div class="flex flex-col sm:flex-row sm:items-center gap-5 pb-6 mb-6 border-b border-gray-100">
            <x-avatar :usuario="$paciente" tamano="h-24 w-24" texto="text-3xl" class="shadow-sm ring-4 ring-[#fde7ed]" />

            <div class="flex-1">
                <h3 class="font-semibold text-gray-800 text-sm">Foto de perfil</h3>
                <p class="text-gray-500 text-xs mt-0.5 mb-3">JPG, PNG o WebP de hasta {{ round(config('imagenes.max_kb') / 1024) }} MB. Se recorta en cuadrado y solo la ves tú y la clínica.</p>

                @if($errors->foto->any())
                    <p class="text-red-600 text-xs mb-2" role="alert">{{ $errors->foto->first() }}</p>
                @endif

                <form method="POST" action="{{ route('paciente.perfil.foto') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row sm:items-center gap-3">
                    @csrf
                    <label for="foto" class="sr-only">Elige una foto</label>
                    <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" required
                           class="block w-full sm:w-auto text-sm text-gray-600 file:mr-3 file:min-h-11 file:rounded-xl file:border-0 file:bg-[#fde7ed] file:px-4 file:py-2 file:text-sm file:font-medium file:text-[#a13153] hover:file:bg-[#fbd3df]">
                    <button type="submit" class="min-h-11 bg-[#c03a63] text-white text-sm font-medium px-5 py-2.5 rounded-xl hover:bg-[#a13153] transition-all duration-300">
                        {{ $paciente->foto ? 'Cambiar foto' : 'Subir foto' }}
                    </button>
                </form>

                @if($paciente->foto)
                    <form method="POST" action="{{ route('paciente.perfil.foto.quitar') }}" class="mt-2" onsubmit="return confirm('¿Quitar tu foto de perfil?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="min-h-11 text-sm font-medium text-red-600 hover:text-red-700 px-1 py-2 transition-colors duration-300">Quitar foto</button>
                    </form>
                @endif
            </div>
        </div>

        @if($errors->datos->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->datos->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('paciente.perfil.datos') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-1.5">
                <label for="name" class="text-gray-600 text-sm font-medium">Nombre</label>
                <input type="text" id="name" name="name" value="{{ old('name', $paciente->name) }}" required maxlength="255"
                       class="{{ $claseInput }} {{ $errors->datos->has('name') ? 'border-red-300' : 'border-gray-200' }}">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="apellidos" class="text-gray-600 text-sm font-medium">Apellidos</label>
                <input type="text" id="apellidos" name="apellidos" value="{{ old('apellidos', $paciente->apellidos) }}" required maxlength="255"
                       class="{{ $claseInput }} {{ $errors->datos->has('apellidos') ? 'border-red-300' : 'border-gray-200' }}">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-gray-600 text-sm font-medium">Correo electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email', $paciente->email) }}" required maxlength="255"
                       class="{{ $claseInput }} {{ $errors->datos->has('email') ? 'border-red-300' : 'border-gray-200' }}">
                <span class="text-gray-400 text-xs">
                    @if($paciente->email_verified_at)
                        Verificado. Si lo cambias, tendrás que verificar el nuevo.
                    @else
                        Aún sin verificar. Te enviamos un enlace a esta dirección.
                    @endif
                </span>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="telefono" class="text-gray-600 text-sm font-medium">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" value="{{ old('telefono', $paciente->telefono) }}" maxlength="20"
                       placeholder="Ej. 7531234567"
                       class="{{ $claseInput }} {{ $errors->datos->has('telefono') ? 'border-red-300' : 'border-gray-200' }}">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="fecha_nacimiento" class="text-gray-600 text-sm font-medium">Fecha de nacimiento</label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" max="{{ now()->subDay()->toDateString() }}"
                       value="{{ old('fecha_nacimiento', $paciente->fecha_nacimiento?->format('Y-m-d')) }}"
                       class="{{ $claseInput }} {{ $errors->datos->has('fecha_nacimiento') ? 'border-red-300' : 'border-gray-200' }}">
            </div>

            <div class="flex flex-col gap-1.5">
                <span class="text-gray-600 text-sm font-medium">Sexo</span>
                @include('partials.selector-sexo', ['nombre' => 'sexo', 'valor' => old('sexo', $paciente->sexo)])
            </div>

            <div class="md:col-span-2 flex justify-end pt-2">
                <button type="submit"
                        class="w-full md:w-auto bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>

    {{-- Seguridad --}}
    <div x-show="pestana === 'seguridad'" x-cloak class="bg-white rounded-2xl shadow-sm p-6">
        <h2 class="font-bold text-gray-800 text-lg mb-1">Cambiar contraseña</h2>
        <p class="text-gray-400 text-sm mb-5">Al cambiarla se cerrarán tus sesiones en otros dispositivos</p>

        @if($errors->password->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->password->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('paciente.perfil.password') }}" class="flex flex-col gap-4 max-w-md">
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-1.5">
                <label for="password_actual" class="text-gray-600 text-sm font-medium">Contraseña actual</label>
                <input type="password" id="password_actual" name="password_actual" required autocomplete="current-password"
                       class="{{ $claseInput }} {{ $errors->password->has('password_actual') ? 'border-red-300' : 'border-gray-200' }}">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-gray-600 text-sm font-medium">Contraseña nueva</label>
                <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password"
                       class="{{ $claseInput }} {{ $errors->password->has('password') ? 'border-red-300' : 'border-gray-200' }}">
                <span class="text-gray-400 text-xs">Mínimo 8 caracteres.</span>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="password_confirmation" class="text-gray-600 text-sm font-medium">Confirmar contraseña nueva</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password"
                       class="{{ $claseInput }} border-gray-200">
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                        class="w-full sm:w-auto bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                    Cambiar contraseña
                </button>
            </div>
        </form>
    </div>


</div>

</x-paciente.layout>
