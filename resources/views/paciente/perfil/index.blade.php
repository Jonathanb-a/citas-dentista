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
        <p class="font-light text-gray-400 text-sm mt-1">Tus datos, tu contraseña y tu expediente clínico</p>
    </div>

    {{-- Pestañas --}}
    <div class="flex gap-2 overflow-x-auto pb-2 mb-6">
        @foreach([
            'datos' => 'Mis datos',
            'seguridad' => 'Seguridad',
            'expediente' => 'Mi expediente',
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

    {{-- Mi expediente --}}
    <div x-show="pestana === 'expediente'" x-cloak>
        @include('paciente.perfil.partials.expediente')
    </div>

</div>

</x-paciente.layout>
