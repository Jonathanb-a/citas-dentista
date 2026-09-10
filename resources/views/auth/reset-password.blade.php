<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña — Dentissa</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FFF6F6]" style="font-family: 'Montserrat', sans-serif;">

<div class="min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-[420px]">

        {{-- Logo --}}
        <div class="flex justify-center mb-8">
            <a href="/">
                <img src="{{ asset('img/logo-dentista.png') }}" alt="Dentissa" class="h-24 w-auto object-contain">
            </a>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-sm p-8 flex flex-col gap-5">

            <div class="flex flex-col gap-1">
                <h1 class="font-bold text-gray-800 text-2xl">Crea tu nueva contraseña</h1>
                <p class="font-light text-gray-400 text-sm">Escribe y confirma tu nueva contraseña para la cuenta.</p>
            </div>

            {{-- Errores --}}
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                    @foreach ($errors->all() as $error)
                        <p class="text-red-500 text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Correo electrónico</label>
                    <input type="email"
                           name="email"
                           value="{{ $email ?? old('email') }}"
                           placeholder="correo@ejemplo.com"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Nueva contraseña</label>
                    <input type="password"
                           name="password"
                           placeholder="Mínimo 8 caracteres"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Confirmar contraseña</label>
                    <input type="password"
                           name="password_confirmation"
                           placeholder="Repite la contraseña"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                </div>

                <button type="submit"
                        class="w-full bg-[#d75078] text-white text-sm font-semibold py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300 mt-2">
                    RESTABLECER CONTRASEÑA
                </button>
            </form>

        </div>

    </div>

</div>

</body>
</html>