{{-- Acciones sobre una cuenta de personal. Parámetros: $usuario, $clases, $contenedor (opcional) --}}
@php
    $contenedor = $contenedor ?? '';
    $esYo = $usuario->id === auth()->id();
    $datosEditar = [
        'id' => $usuario->id,
        'name' => $usuario->name,
        'apellidos' => $usuario->apellidos,
        'email' => $usuario->email,
        'telefono' => $usuario->telefono,
        'sexo' => $usuario->sexo,
        'role' => $usuario->role,
        'esYo' => $esYo,
    ];
@endphp

<div class="{{ $contenedor }}">
    <button type="button" @click="editar({{ Illuminate\Support\Js::from($datosEditar) }})"
            class="{{ $clases }} bg-[#fde7ed] text-[#d75078] hover:bg-[#fbd3df] transition-all duration-200">
        Editar
    </button>
</div>

<form method="POST" action="{{ route('admin.personal.restablecer-password', $usuario->id) }}" class="{{ $contenedor }}"
      onsubmit="return confirm('{{ $esYo ? '¿Generar una contraseña nueva para tu cuenta? Tendrás que iniciar sesión otra vez.' : '¿Generar una contraseña nueva para esta cuenta? La actual dejará de funcionar.' }}');">
    @csrf
    @method('PATCH')
    <button type="submit" class="{{ $clases }} border border-gray-200 text-gray-600 hover:bg-[#FFF6F6] hover:text-[#d75078] transition-all duration-200 whitespace-nowrap">
        Restablecer
    </button>
</form>

@if(!$esYo)
    <form method="POST" action="{{ route('admin.personal.toggle', $usuario->id) }}" class="{{ $contenedor }}"
          onsubmit="return confirm('{{ $usuario->activo ? '¿Desactivar esta cuenta? Ya no podrá iniciar sesión.' : '¿Activar esta cuenta?' }}');">
        @csrf
        @method('PATCH')
        @if($usuario->activo)
            <button type="submit" class="{{ $clases }} bg-red-50 text-red-600 hover:bg-red-100 transition-all duration-200">Desactivar</button>
        @else
            <button type="submit" class="{{ $clases }} bg-green-50 text-green-600 hover:bg-green-100 transition-all duration-200">Activar</button>
        @endif
    </form>
@endif
