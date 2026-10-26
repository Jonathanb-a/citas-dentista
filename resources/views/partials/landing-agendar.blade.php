{{--
    Franja final para agendar. Título y texto editables (agendar.*, pestaña "Imágenes y
    textos"); los botones son fijos: crear cuenta y agendar (si no hay sesión, Laravel
    pide entrar y después vuelve a agendar).
--}}
@php
    $focoAgendar = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#a13153]';
@endphp
<section id="agendar" class="w-full px-6 md:px-[108px] py-16 md:py-20" style="font-family: 'Montserrat', sans-serif;">
    <div data-aparecer class="mx-auto flex max-w-[1100px] flex-col items-center gap-6 rounded-3xl bg-linear-to-br from-[#fde7ed] to-[#fbd3df] px-6 py-12 text-center md:px-16">
        <h2 class="font-extrabold text-gray-800 text-3xl md:text-5xl leading-tight tracking-tight">
            {{ $landing->titulo('agendar.titulo') }}
        </h2>
        <p class="max-w-[640px] text-gray-700 text-base md:text-lg leading-relaxed">
            {{ $landing->texto('agendar.texto') }}
        </p>
        <div class="flex w-full flex-col justify-center gap-3 sm:w-auto sm:flex-row">
            <a href="{{ route('paciente.citas.create') }}"
               class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-[#c03a63] px-7 py-3.5 text-sm md:text-base font-semibold text-white hover:bg-[#a13153] transition-[background-color] duration-300 {{ $focoAgendar }}">
                Agendar cita
            </a>
            <a href="/register"
               class="inline-flex min-h-11 items-center justify-center rounded-2xl border-2 border-[#c03a63] bg-white px-7 py-3 text-sm md:text-base font-semibold text-[#a13153] hover:bg-[#FFF6F6] transition-[background-color] duration-300 {{ $focoAgendar }}">
                Crear cuenta
            </a>
        </div>
    </div>
</section>
