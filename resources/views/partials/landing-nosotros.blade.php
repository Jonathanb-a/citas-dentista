{{--
    Nosotros (misión y visión) y certificados en bloques compactos, debajo de
    tratamientos. Textos e imágenes editables: nosotros.* y certificados.* (pestaña
    "Imágenes y textos" del panel).
--}}
<section id="nosotros" class="w-full bg-[#FFF6F6] py-16 md:py-24 px-6 md:px-[108px] scroll-mt-[82px] md:scroll-mt-[100px]" style="font-family: 'Montserrat', sans-serif;">

    <div data-aparecer class="text-center mb-12">
        <h2 class="font-bold text-gray-800 text-3xl md:text-5xl">NOSOTROS</h2>
    </div>

    <div class="mx-auto grid max-w-[1100px] gap-6 md:grid-cols-2">
        @foreach([
            ['Misión', 'nosotros.mision_imagen', 'nosotros.mision_texto', 0],
            ['Visión', 'nosotros.vision_imagen', 'nosotros.vision_texto', 100],
        ] as [$titulo, $imagen, $texto, $retraso])
            <article data-aparecer style="--retraso: {{ $retraso }}ms" class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm">
                <img src="{{ $landing->imagen($imagen) }}" loading="lazy" decoding="async" alt="{{ $titulo }}"
                     width="550" height="192" class="h-48 w-full object-cover bg-[#fde7ed]">
                <div class="flex flex-col gap-2 p-6">
                    <h3 class="font-bold text-[#c03a63] text-xl">{{ $titulo }}</h3>
                    <p class="text-gray-600 text-sm md:text-base leading-relaxed">{{ $landing->texto($texto) }}</p>
                </div>
            </article>
        @endforeach
    </div>

    {{-- Certificados: mismo bloque, en horizontal --}}
    <article id="certificados" data-aparecer class="mx-auto mt-6 flex max-w-[1100px] flex-col items-center gap-6 rounded-2xl bg-white p-6 shadow-sm sm:flex-row scroll-mt-[100px] md:scroll-mt-[120px]">
        <img src="{{ $landing->imagen('certificados.imagen') }}" loading="lazy" decoding="async" alt="Certificado"
             width="160" height="120" class="h-[120px] w-40 shrink-0 object-contain">
        <div class="flex flex-col gap-2 text-center sm:text-left">
            <h3 class="font-bold text-[#c03a63] text-xl">Certificados</h3>
            <p class="text-gray-600 text-sm md:text-base leading-relaxed">{{ $landing->texto('certificados.texto') }}</p>
        </div>
    </article>

</section>
