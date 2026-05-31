<div>
    <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro — Dentissa</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FFF6F6]">

<div class="min-h-screen flex">

    {{-- Lado izquierdo — Formulario --}}
    <div class="w-full md:w-1/2 flex flex-col items-center justify-center px-8 md:px-16 py-12">

        {{-- Logo --}}
        <a href="/" class="mb-6">
            <img src="{{ asset('img/logo-dentista.png') }}"
                 alt="Melissa López N."
                 class="h-20 w-auto object-contain">
        </a>

        {{-- Card --}}
        <div class="w-full max-w-[420px] bg-white rounded-2xl shadow-sm p-8 flex flex-col gap-5">

            {{-- Titulo --}}
            <div class="flex flex-col gap-1">
                <h1 class="font-bold text-gray-800 text-2xl"
                    style="font-family: 'Montserrat', sans-serif;">
                    Crear cuenta 🦷
                </h1>
                <p class="font-light text-gray-400 text-sm"
                   style="font-family: 'Montserrat', sans-serif;">
                    Regístrate para agendar tus citas fácilmente
                </p>
            </div>

            {{-- Formulario --}}
            <form method="POST" action="/register" class="flex flex-col gap-4">
                @csrf

                {{-- Nombre completo --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium"
                           style="font-family: 'Montserrat', sans-serif;">
                        Nombre completo
                    </label>
                    <input type="text"
                           name="name"
                           placeholder="María García"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300"
                           style="font-family: 'Montserrat', sans-serif;">
                </div>

                {{-- Email --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium"
                           style="font-family: 'Montserrat', sans-serif;">
                        Correo electrónico
                    </label>
                    <input type="email"
                           name="email"
                           placeholder="tucorreo@gmail.com"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300"
                           style="font-family: 'Montserrat', sans-serif;">
                </div>

                {{-- Telefono --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium"
                           style="font-family: 'Montserrat', sans-serif;">
                        Número de teléfono
                    </label>
                    <input type="tel"
                           name="phone"
                           placeholder="+52 999 123 1234"
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

                {{-- Confirmar Contraseña --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium"
                           style="font-family: 'Montserrat', sans-serif;">
                        Confirmar contraseña
                    </label>
                    <input type="password"
                           name="password_confirmation"
                           placeholder="••••••••"
                           class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300"
                           style="font-family: 'Montserrat', sans-serif;">
                </div>

                {{-- Terminos --}}
                <div class="flex items-start gap-3">
                    <input type="checkbox"
                           name="terms"
                           id="terms"
                           class="mt-1 accent-[#d75078]">
                    <label for="terms"
                           class="text-gray-500 text-xs leading-relaxed"
                           style="font-family: 'Montserrat', sans-serif;">
                        Acepto los
                        <a href="#" class="text-[#d75078] hover:underline">términos y condiciones</a>
                        y el
                        <a href="#" class="text-[#d75078] hover:underline">aviso de privacidad</a>
                    </label>
                </div>

                {{-- Boton --}}
                <button type="submit"
                        class="w-full bg-[#d75078] text-white text-sm font-semibold py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300 mt-2"
                        style="font-family: 'Montserrat', sans-serif;">
                    CREAR CUENTA
                </button>

            </form>

            {{-- Divider --}}
            <div class="flex items-center gap-3">
                <div class="flex-1 h-px bg-gray-100"></div>
                <span class="text-gray-400 text-xs" style="font-family: 'Montserrat', sans-serif;">o</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>

            {{-- Ya tienes cuenta --}}
            <a href="/login"
               class="w-full text-center border border-[#d75078] text-[#d75078] text-sm font-semibold py-3 rounded-xl hover:bg-[#fde7ed] transition-all duration-300"
               style="font-family: 'Montserrat', sans-serif;">
                ¿Ya tienes cuenta? Inicia sesión
            </a>

        </div>

        {{-- Volver al inicio --}}
        <a href="/"
           class="mt-6 text-xs text-gray-400 hover:text-[#d75078] transition-colors duration-300"
           style="font-family: 'Montserrat', sans-serif;">
            ← Volver al inicio
        </a>

    </div>

    {{-- Lado derecho — Imagen --}}
    <div class="hidden md:block w-1/2 relative">
        <img src="{{ asset('img/login-bg.png') }}"
             alt="Dentista"
             class="w-full h-full object-cover">

        {{-- Overlay --}}
        <div class="absolute inset-0 bg-gradient-to-t from-[#d75078]/60 to-transparent flex flex-col justify-end p-12">
            <h2 class="font-bold text-white text-3xl mb-2"
                style="font-family: 'Montserrat', sans-serif;">
                Únete a nuestra familia dental 🦷
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
</div>
