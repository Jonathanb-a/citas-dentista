{{--
    Contacto: teléfono con horario, WhatsApp, dirección y mapa. Todo sale de contacto.*
    (pestaña "Contacto" del panel).
--}}
@php
    $focoContacto = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#a13153]';
    // 9991231234 → 999 123 1234, para leerlo mejor
    $telefonoVisible = preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '$1 $2 $3', $landing->valor('contacto.telefono'));
    $tarjeta = 'flex flex-col gap-3 rounded-2xl bg-[#FFF6F6] p-6 shadow-sm';
    $icono = 'flex h-12 w-12 items-center justify-center rounded-2xl bg-[#fde7ed] text-[#d75078]';
@endphp
<section id="contacto" class="w-full bg-white py-16 md:py-24 px-6 md:px-[108px] scroll-mt-[82px] md:scroll-mt-[100px]" style="font-family: 'Montserrat', sans-serif;">

    <div data-aparecer class="text-center mb-12">
        <h2 class="font-bold text-gray-800 text-3xl md:text-5xl">CONTACTO</h2>
        <p class="text-gray-600 text-sm md:text-base mt-3">¿Prefieres hablar con alguien? Llámanos o escríbenos.</p>
    </div>

    <div class="mx-auto grid max-w-[1100px] gap-6 lg:grid-cols-2">

        <div class="flex flex-col gap-6">
            {{-- Teléfono --}}
            <div data-aparecer class="{{ $tarjeta }}">
                <span class="{{ $icono }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </span>
                <h3 class="font-bold text-gray-800 text-lg">Llamar por teléfono</h3>
                <p class="text-gray-600 text-sm">Habla directamente con nuestra asistente. Horario: {{ $landing->texto('contacto.horario') }}</p>
                <a href="{{ $landing->enlaceTelefono() }}"
                   class="inline-flex min-h-11 items-center justify-center self-start rounded-2xl bg-[#c03a63] px-6 py-3 text-sm font-semibold text-white hover:bg-[#a13153] transition-[background-color] duration-300 {{ $focoContacto }}">
                    Llamar al {{ $telefonoVisible }}
                </a>
            </div>

            {{-- WhatsApp --}}
            <div data-aparecer style="--retraso: 100ms" class="{{ $tarjeta }}">
                <span class="{{ $icono }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </span>
                <h3 class="font-bold text-gray-800 text-lg">Escribir por WhatsApp</h3>
                <p class="text-gray-600 text-sm">Mándanos un mensaje y te respondemos en horario de atención.</p>
                <a href="{{ $landing->enlaceWhatsapp() }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex min-h-11 items-center justify-center self-start rounded-2xl bg-[#15626C] px-6 py-3 text-sm font-semibold text-white hover:bg-[#0f4a52] transition-[background-color] duration-300 {{ $focoContacto }}">
                    Abrir WhatsApp <span class="sr-only">(se abre en otra pestaña)</span>
                </a>
            </div>

            {{-- Dirección --}}
            <div data-aparecer style="--retraso: 200ms" class="{{ $tarjeta }}">
                <span class="{{ $icono }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
                <h3 class="font-bold text-gray-800 text-lg">Dónde estamos</h3>
                <address class="not-italic text-gray-600 text-sm">
                    <span class="block font-semibold text-gray-800">{{ $landing->texto('contacto.direccion1') }}</span>
                    {{ $landing->texto('contacto.direccion2') }}
                </address>
            </div>
        </div>

        {{-- Mapa (el foco entra al mapa: el contorno va en el contenedor) --}}
        <div data-aparecer style="--retraso: 300ms" class="min-h-[320px] overflow-hidden rounded-2xl shadow-sm focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-[#a13153]">
            <iframe
                title="Mapa de ubicación del consultorio"
                src="{{ $landing->texto('contacto.mapa') }}"
                class="h-full min-h-[320px] w-full"
                style="border:0;"
                allowfullscreen=""
                loading="lazy">
            </iframe>
        </div>

    </div>

</section>
