{{--
    Tratamientos: servicios activos agrupados por especialidad activa ($tratamientos,
    de LandingController). Son los mismos que el paciente puede agendar. Sin precio:
    el sistema no lo maneja. Si no hay servicios, la vista no incluye esta sección.
--}}
@php
    // 45 → "45 min", 60 → "1 h", 90 → "1 h 30 min"
    $duracion = function (int $minutos): string {
        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        if ($horas === 0) {
            return $resto . ' min';
        }

        return $horas . ' h' . ($resto ? ' ' . $resto . ' min' : '');
    };
@endphp
<section id="tratamientos" class="w-full bg-white py-16 md:py-24 px-6 md:px-[108px] scroll-mt-[82px] md:scroll-mt-[100px]" style="font-family: 'Montserrat', sans-serif;">

    {{-- Titulo (scroll-mt: el navbar es fijo y taparía el título al llegar con #tratamientos) --}}
    <div data-aparecer class="text-center mb-12 md:mb-16">
        <h2 class="font-bold text-gray-800 text-3xl md:text-5xl">TRATAMIENTOS</h2>
        <p class="text-gray-600 text-sm md:text-base mt-3">Los servicios que puedes agendar en línea desde tu cuenta.</p>
    </div>

    <div class="flex flex-col gap-12">
        @foreach ($tratamientos as $especialidad)
            <div>
                <div data-aparecer class="mb-6">
                    <h3 class="font-bold text-[#c03a63] text-xl md:text-2xl">{{ $especialidad->nombre }}</h3>
                    @if ($especialidad->descripcion)
                        <p class="text-gray-600 text-sm mt-1">{{ $especialidad->descripcion }}</p>
                    @endif
                </div>

                <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($especialidad->servicios as $servicio)
                        {{-- La aparición va en el <li> y el hover en la tarjeta: la animación fija un transform que anularía el del hover --}}
                        <li data-aparecer style="--retraso: {{ ($loop->index % 3) * 100 }}ms">
                            <div class="h-full flex flex-col gap-3 rounded-2xl bg-[#FFF6F6] p-6 shadow-sm motion-safe:transition-transform motion-safe:duration-300 motion-safe:hover:-translate-y-1">
                                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#fde7ed] text-[#d75078]">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 5.5C10.5 4 8 3.5 6.3 4.6 4.2 6 4.3 9 5.3 11.3c.7 1.6.9 3.1 1.1 4.8.2 1.8.6 3.9 1.9 3.9 1.2 0 1.4-1.9 1.7-3.6.2-1.1.6-2 2-2s1.8.9 2 2c.3 1.7.5 3.6 1.7 3.6 1.3 0 1.7-2.1 1.9-3.9.2-1.7.4-3.2 1.1-4.8 1-2.3 1.1-5.3-1-6.7C16 3.5 13.5 4 12 5.5Z"/>
                                    </svg>
                                </span>
                                <h4 class="font-semibold text-gray-800 text-lg">{{ $servicio->nombre }}</h4>
                                @if ($servicio->descripcion)
                                    <p class="text-gray-600 text-sm leading-relaxed">{{ $servicio->descripcion }}</p>
                                @endif
                                <p class="mt-auto flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="h-4 w-4 text-[#d75078]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                                    </svg>
                                    Duración aprox.: {{ $duracion((int) $servicio->duracion_minutos) }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

</section>
