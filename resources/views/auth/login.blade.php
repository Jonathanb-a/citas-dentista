<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Dentissa</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FFF6F6]">

<div class="min-h-screen flex">

    {{-- Lado izquierdo — Formulario --}}
    <div class="w-full md:w-1/2 flex flex-col items-center justify-center px-8 md:px-16 py-12">

        {{-- Logo --}}
        <a href="/" class="mb-8">
            <img src="{{ asset('img/logo-dentista.png') }}"
                 alt="Melissa López N."
                 class="h-27 w-auto object-contain">
        </a>

        {{-- Card --}}
        <div class="w-full max-w-[400px] bg-white rounded-2xl shadow-sm p-8 flex flex-col gap-5">

            {{-- Titulo --}}
            <div class="flex flex-col gap-1">
                <h1 class="font-bold text-gray-800 text-2xl"
                    style="font-family: 'Montserrat', sans-serif;">
                    Bienvenid@
                </h1>
                <p class="font-light text-gray-400 text-sm"
                   style="font-family: 'Montserrat', sans-serif;">
                    Inicia sesión para gestionar tus citas
                </p>
            </div>

            {{-- Errores --}}
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                    @foreach ($errors->all() as $error)
                        <p class="text-red-500 text-sm" style="font-family: 'Montserrat', sans-serif;">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Formulario --}}
            <form method="POST" action="/login" class="flex flex-col gap-4">
                @csrf

                {{-- Correo o teléfono --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium"
                           style="font-family: 'Montserrat', sans-serif;">
                        Correo o teléfono
                    </label>
                    <input type="text"
                           name="identificador"
                           value="{{ old('identificador') }}"
                           placeholder="correo@ejemplo.com o 9991234567"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300"
                           style="font-family: 'Montserrat', sans-serif;">
                </div>

                {{-- Contraseña --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium"
                           style="font-family: 'Montserrat', sans-serif;">
                        Contraseña
                    </label>
                    <input type="password"
                           name="password"
                           placeholder="••••••••"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300"
                           style="font-family: 'Montserrat', sans-serif;">
                </div>

                {{-- Olvidaste tu contraseña --}}
                <div class="flex justify-end">
                    <a href="/forgot-password"
                       class="text-xs text-[#d75078] hover:text-[#f285a2] transition-colors duration-300"
                       style="font-family: 'Montserrat', sans-serif;">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

                {{-- Boton --}}
                <button type="submit"
                        class="w-full bg-[#d75078] text-white text-sm font-semibold py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300 mt-2"
                        style="font-family: 'Montserrat', sans-serif;">
                    INICIAR SESIÓN
                </button>

            </form>

            {{-- Divider --}}
            <div class="flex items-center gap-3">
                <div class="flex-1 h-px bg-gray-100"></div>
                <span class="text-gray-400 text-xs" style="font-family: 'Montserrat', sans-serif;">o</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>

            {{-- Registrate --}}
            <a href="/register"
               class="w-full text-center border border-[#d75078] text-[#d75078] text-sm font-semibold py-3 rounded-xl hover:bg-[#fde7ed] transition-all duration-300"
               style="font-family: 'Montserrat', sans-serif;">
                ¿No tienes cuenta? Regístrate
            </a>

        </div>

        {{-- Volver al inicio --}}
        <a href="/"
           class="mt-6 text-xs text-gray-400 hover:text-[#d75078] transition-colors duration-300 flex items-center gap-1"
           style="font-family: 'Montserrat', sans-serif;">
            ← Volver al inicio
        </a>

    </div>

    {{-- Lado derecho — Imagen --}}
    <div class="hidden md:block w-1/2 relative">
        <img src="{{ asset('img/login-bg.png') }}"
             alt="Dentista"
             class="w-full h-full object-cover">

        {{-- Overlay con texto --}}
        <div class="absolute inset-0 bg-gradient-to-t from-[#d75078]/60 to-transparent flex flex-col justify-end p-12">
            <h2 class="font-bold text-white text-3xl mb-2"
                style="font-family: 'Montserrat', sans-serif;">
                Tu sonrisa, nuestra prioridad
            </h2>
            <p class="font-light text-white/80 text-sm"
               style="font-family: 'Montserrat', sans-serif;">
                Agenda y gestiona tus citas desde cualquier lugar.
            </p>
        </div>
    </div>

</div>

</body>
</html>