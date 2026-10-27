{{--
    Pie de página: enlaces a las secciones que se muestran ($navegacion), redes de
    contacto.* y las líneas pie.derechos y pie.ubicacion (pestaña "Contacto" del panel).
--}}
@php
    $focoPie = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#a13153]';
    $redes = [
        ['Instagram', $landing->texto('contacto.instagram'), 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z'],
        ['Facebook', $landing->texto('contacto.facebook'), 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z'],
        ['WhatsApp', $landing->enlaceWhatsapp(), 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z'],
    ];
@endphp
<footer class="w-full bg-[#FFF6F6] px-6 md:px-[108px] lg:px-12 xl:px-[108px] pt-8 pb-4" style="font-family: 'Montserrat', sans-serif;">

    {{-- Móvil: apilado y centrado. Desde lg: una fila (logo · enlaces · redes); la ubicación va al lado del logo desde xl --}}
    <div class="mx-auto flex max-w-[1100px] flex-col items-center gap-4 lg:flex-row lg:justify-between lg:gap-8">

        {{-- Logo y ubicación --}}
        <div class="flex shrink-0 flex-col items-center gap-1 text-center lg:items-start lg:text-left xl:flex-row xl:items-center xl:gap-3">
            <img src="{{ asset('img/logo-dentista.png') }}" loading="lazy" decoding="async"
                 alt="Melissa López N." class="h-14 w-auto object-contain">
            <p class="text-gray-600 text-xs">{{ $landing->texto('pie.ubicacion') }}</p>
        </div>

        {{-- Secciones: una línea que puede saltar de renglón en pantallas chicas --}}
        <nav aria-label="Secciones de la página">
            <ul class="flex flex-wrap items-center justify-center gap-x-4 lg:flex-nowrap xl:gap-x-6">
                @foreach($navegacion ?? ['#inicio' => 'Inicio', '#nosotros' => 'Nosotros', '#contacto' => 'Contacto'] as $ancla => $titulo)
                    <li>
                        <a href="{{ $ancla }}" class="inline-flex min-h-11 items-center px-1 text-gray-700 text-sm hover:text-[#c03a63] transition-colors duration-300 rounded {{ $focoPie }}">{{ $titulo }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- Redes (cada ícono con su nombre para lectores de pantalla) --}}
        <div class="flex shrink-0 items-center gap-2">
            @foreach($redes as [$red, $enlace, $icono])
                <a href="{{ $enlace }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $red }} (se abre en otra pestaña)"
                   class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-gray-600 shadow-sm hover:text-[#c03a63] transition-colors duration-300 {{ $focoPie }}">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icono }}"/></svg>
                </a>
            @endforeach
        </div>

    </div>

    {{-- Derechos: una fila compacta bajo un borde fino --}}
    <div class="mx-auto mt-4 max-w-[1100px] border-t border-[#fbd3df] pt-3 text-center">
        <p class="text-gray-600 text-xs">{{ $landing->texto('pie.derechos') }}</p>
    </div>

</footer>
