{{--
    Opiniones de pacientes ($testimonios, de LandingController): solo las que tienen
    la autorización marcada en el panel. Sin fotos. La landing no incluye esta sección
    si no hay ninguna.
--}}
<section id="testimonios" class="w-full bg-[#FFF6F6] py-16 md:py-24 px-6 md:px-[108px] scroll-mt-[82px] md:scroll-mt-[100px]" style="font-family: 'Montserrat', sans-serif;">

    <div data-aparecer class="text-center mb-12 md:mb-16">
        <h2 class="font-bold text-gray-800 text-3xl md:text-5xl">OPINIONES DE PACIENTES</h2>
        <p class="text-gray-600 text-sm md:text-base mt-3">Lo que nos compartieron pacientes de la clínica, con su autorización.</p>
    </div>

    <ul class="flex flex-wrap justify-center gap-6">
        @foreach($testimonios as $opinion)
            @php $calificacion = max(1, min(5, (int) $opinion['calificacion'])); @endphp
            <li data-aparecer style="--retraso: {{ ($loop->index % 3) * 100 }}ms"
                class="w-full sm:w-[calc(50%-12px)] lg:w-[calc(33.333%-16px)]">
                <figure class="h-full flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm">
                    {{-- Estrellas: el lector de pantalla lee el número, no los íconos --}}
                    <p class="flex gap-1 text-[#d75078]" role="img" aria-label="Calificación: {{ $calificacion }} de 5">
                        @for($estrella = 1; $estrella <= 5; $estrella++)
                            <svg class="h-5 w-5 {{ $estrella <= $calificacion ? '' : 'text-gray-300' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/>
                            </svg>
                        @endfor
                    </p>
                    <blockquote class="text-gray-700 text-sm md:text-base leading-relaxed">
                        <p>“{{ $opinion['texto'] }}”</p>
                    </blockquote>
                    <figcaption class="mt-auto font-semibold text-gray-800 text-sm">{{ $opinion['nombre'] }}</figcaption>
                </figure>
            </li>
        @endforeach
    </ul>

</section>
