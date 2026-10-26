<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Dentissa</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FFF6F6] text-gray-700" style="font-family: 'Montserrat', sans-serif;">

@php
    // Vidrio de los campos: borde rosa al enfocar, rojo si hay error (mismo estilo que el registro)
    $vidrio = 'rounded-2xl border bg-white/60 backdrop-blur-sm transition-colors duration-300 focus-within:border-[#d75078] focus-within:bg-[#fde7ed]/40 focus-within:ring-2 focus-within:ring-[#d75078]/40';

    // Los errores del login (datos incorrectos, cuenta desactivada) llegan en "identificador"
    $errorIdentificador = $errors->has('identificador');
    $errorPassword = $errors->has('password');

    // Foto del consultorio: si existe public/img/registro-consultorio.jpg se usa; si no, un placeholder con el logo
    $fotoConsultorio = file_exists(public_path('img/registro-consultorio.jpg')) ? 'img/registro-consultorio.jpg' : null;

    $beneficios = [
        ['Agenda en línea', 'Elige servicio, día y hora entre los horarios libres, hasta 3 meses por adelantado.',
            'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z'],
        ['Confirma o cancela', 'Cuando la clínica acepta tu cita, confirma tu asistencia o cancélala desde tu cuenta.',
            'M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['Tu expediente a la mano', 'Consulta tu diagnóstico y plan de tratamiento en "Mi perfil".',
            'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2M9 13h6M9 17h4'],
    ];

    $foco = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#d75078]';
@endphp

<div class="min-h-[100dvh] flex flex-col md:flex-row">

    {{-- Columna izquierda — Formulario (en móvil va primero) --}}
    <main class="flex-1 flex items-center justify-center px-6 py-10 md:px-12">
        <div class="w-full max-w-md flex flex-col gap-6">

            <a href="/" class="registro-entrada self-start rounded-xl {{ $foco }}" style="--retraso: 0ms">
                <img src="{{ asset('img/logo-dentista.png') }}"
                     alt="Melissa López N. — inicio"
                     class="h-16 w-auto object-contain">
            </a>

            <div class="flex flex-col gap-2">
                <h1 class="registro-entrada text-4xl md:text-5xl font-semibold leading-tight tracking-tight text-gray-800" style="--retraso: 100ms">
                    Bienvenid@
                </h1>
                <p class="registro-entrada text-gray-500" style="--retraso: 200ms">
                    Inicia sesión para gestionar tus citas
                </p>
            </div>

            {{-- Errores --}}
            @if ($errors->any())
                <div id="errores-login" class="registro-entrada bg-red-50 border border-red-200 rounded-2xl px-4 py-3" role="alert" style="--retraso: 250ms">
                    @foreach ($errors->all() as $error)
                        <p class="text-red-500 text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Formulario --}}
            <form method="POST" action="/login" class="flex flex-col gap-5">
                @csrf

                {{-- Correo o teléfono --}}
                <div class="registro-entrada flex flex-col gap-1.5" style="--retraso: 300ms">
                    <label for="identificador" class="text-sm font-medium text-gray-600">Correo o teléfono</label>
                    <div class="{{ $vidrio }} {{ $errorIdentificador ? 'border-red-400' : 'border-[#fbd3df]' }}">
                        <input type="text"
                               id="identificador"
                               name="identificador"
                               value="{{ old('identificador') }}"
                               placeholder="correo@ejemplo.com o 9991234567"
                               autocomplete="username"
                               @if ($errorIdentificador) aria-invalid="true" aria-describedby="errores-login" @endif
                               class="w-full min-h-11 bg-transparent text-sm text-gray-700 placeholder:text-gray-400 p-4 rounded-2xl focus:outline-none">
                    </div>
                </div>

                {{-- Contraseña --}}
                <div class="registro-entrada flex flex-col gap-1.5" style="--retraso: 360ms" x-data="{ visible: false }">
                    <label for="password" class="text-sm font-medium text-gray-600">Contraseña</label>
                    <div class="{{ $vidrio }} {{ $errorPassword ? 'border-red-400' : 'border-[#fbd3df]' }}">
                        <div class="relative">
                            <input type="password"
                                   :type="visible ? 'text' : 'password'"
                                   id="password"
                                   name="password"
                                   placeholder="••••••••"
                                   autocomplete="current-password"
                                   @if ($errorPassword) aria-invalid="true" aria-describedby="errores-login" @endif
                                   class="w-full min-h-11 bg-transparent text-sm text-gray-700 placeholder:text-gray-400 p-4 pr-14 rounded-2xl focus:outline-none">
                            <button type="button"
                                    @click="visible = !visible"
                                    :aria-pressed="visible.toString()"
                                    :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                    aria-label="Mostrar contraseña"
                                    aria-controls="password"
                                    class="absolute inset-y-0 right-1 my-auto flex h-11 w-11 items-center justify-center rounded-xl text-gray-400 hover:text-[#d75078] transition-colors duration-300 {{ $foco }}">
                                {{-- Ojo abierto --}}
                                <svg x-show="!visible" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                                {{-- Ojo tachado --}}
                                <svg x-show="visible" style="display: none" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9.9 4.2A10.4 10.4 0 0 1 12 4c6.5 0 10 8 10 8a17.6 17.6 0 0 1-2.2 3.2M6.6 6.6A17.4 17.4 0 0 0 2 12s3.5 8 10 8a9.8 9.8 0 0 0 5.4-1.6M14.1 14.1a3 3 0 1 1-4.2-4.2M2 2l20 20"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Olvidaste tu contraseña --}}
                <div class="registro-entrada flex justify-end" style="--retraso: 420ms">
                    <a href="/forgot-password"
                       class="inline-flex min-h-11 items-center px-1 text-sm text-[#c03a63] hover:underline rounded {{ $foco }}">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

                {{-- Boton: mismos tonos AA que el registro (#c03a63 = 5.22:1, hover #a13153 = 6.82:1) --}}
                <button type="submit"
                        class="registro-entrada w-full min-h-11 bg-[#c03a63] text-white text-sm font-semibold py-4 rounded-2xl hover:bg-[#a13153] transition-colors duration-300 {{ $foco }}"
                        style="--retraso: 480ms">
                    INICIAR SESIÓN
                </button>
            </form>

            {{-- Registrate --}}
            <p class="registro-entrada text-center text-sm text-gray-500" style="--retraso: 540ms">
                ¿No tienes cuenta?
                <a href="/register" class="inline-flex min-h-11 items-center px-1 font-semibold text-[#c03a63] hover:underline rounded {{ $foco }}">Regístrate</a>
            </p>

            {{-- Volver al inicio --}}
            <a href="/"
               class="registro-entrada self-center inline-flex min-h-11 items-center px-2 text-xs text-gray-500 hover:text-[#c03a63] transition-colors duration-300 rounded {{ $foco }}"
               style="--retraso: 600ms">
                ← Volver al inicio
            </a>
        </div>
    </main>

    {{-- Columna derecha — Consultorio y beneficios (en móvil, debajo del formulario) --}}
    <aside class="flex-1 px-6 pb-10 md:p-4 md:sticky md:top-0 md:h-[100dvh]" aria-label="Qué puedes hacer con tu cuenta">
        <div class="relative flex h-full flex-col gap-4 md:block">

            {{-- Foto del consultorio o placeholder --}}
            <div class="registro-entrada-lateral relative h-48 overflow-hidden rounded-3xl md:absolute md:inset-0 md:h-auto" style="--retraso: 300ms">
                @if ($fotoConsultorio)
                    <img src="{{ asset($fotoConsultorio) }}" alt="Consultorio dental de Melissa López N."
                         class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center md:items-start md:pt-16 bg-linear-to-br from-[#fde7ed] via-[#fbd3df] to-[#f285a2]">
                        <img src="{{ asset('img/logo-dentista.png') }}" alt="" class="h-24 w-auto object-contain opacity-80 md:h-40">
                    </div>
                @endif
            </div>

            {{-- Beneficios --}}
            <ul class="flex flex-col gap-3 md:absolute md:inset-x-8 md:bottom-8">
                @foreach ($beneficios as [$titulo, $texto, $icono])
                    <li class="registro-entrada flex items-start gap-3 rounded-3xl border border-white/60 bg-white/70 p-5 shadow-sm backdrop-blur-xl"
                        style="--retraso: {{ 700 + $loop->index * 200 }}ms">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#fde7ed] text-[#d75078]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="{{ $icono }}"/>
                            </svg>
                        </span>
                        <div class="text-sm leading-snug">
                            <p class="font-semibold text-gray-800">{{ $titulo }}</p>
                            <p class="mt-1 text-gray-600">{{ $texto }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </aside>

</div>

</body>
</html>
