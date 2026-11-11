<x-paciente.layout title="Mi opinión">

@php
    $min = (int) config('testimonios.texto_min');
    $max = (int) config('testimonios.texto_max');
    $estados = [
        'pendiente' => 'bg-yellow-50 text-yellow-700',
        'aprobado' => 'bg-green-50 text-green-700',
        'oculto' => 'bg-gray-100 text-gray-600',
    ];
@endphp

    <div class="mb-8">
        <h1 class="font-bold text-gray-800 text-2xl">Mi opinión</h1>
        <p class="font-light text-gray-500 text-sm mt-1">Cuéntanos cómo fue tu experiencia. Tu opinión ayuda a otras personas a conocer la clínica.</p>
    </div>

    @if(!$testimonio && !$puedeOpinar)
        <div class="bg-white rounded-2xl shadow-sm p-6 max-w-2xl">
            <p class="text-gray-700 text-sm">{{ $motivo }}</p>
            <a href="{{ route('paciente.citas.create') }}"
               class="inline-flex mt-5 bg-[#d75078] text-white text-sm font-medium px-5 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">Agendar una cita</a>
        </div>
    @else
        @if($testimonio)
            <div class="bg-white rounded-2xl shadow-sm p-6 max-w-2xl mb-6">
                <div class="flex flex-wrap items-center gap-3 mb-3">
                    <span class="text-xs font-medium px-3 py-1.5 rounded-full {{ $estados[$testimonio->estado] ?? $estados['pendiente'] }}">{{ $testimonio->etiquetaParaPaciente() }}</span>
                    <p class="flex gap-0.5 text-[#d75078]" role="img" aria-label="Tu calificación: {{ $testimonio->calificacion }} de 5">
                        @for($e = 1; $e <= 5; $e++)
                            <svg class="h-5 w-5 {{ $e <= $testimonio->calificacion ? '' : 'text-gray-300' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/></svg>
                        @endfor
                    </p>
                </div>
                <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-line break-words">“{{ $testimonio->texto }}”</p>
                <p class="text-gray-500 text-xs mt-3">
                    @if($testimonio->estado === 'aprobado')
                        Se muestra en la página con tu nombre de pila y la inicial de tu apellido ({{ $testimonio->nombrePublico() }}).
                    @elseif($testimonio->estado === 'oculto')
                        Tu opinión no se muestra en la página por ahora.
                    @else
                        La doctora la revisará antes de publicarla.
                    @endif
                </p>
            </div>
        @endif

        <form method="POST" action="{{ $testimonio ? route('paciente.testimonio.update') : route('paciente.testimonio.store') }}"
              class="bg-white rounded-2xl shadow-sm p-6 max-w-2xl space-y-6"
              x-data="{ valor: {{ (int) old('calificacion', $testimonio?->calificacion ?? 0) }}, texto: @js((string) old('texto', $testimonio?->texto ?? '')) }">
            @csrf
            @if($testimonio) @method('PUT') @endif

            <h2 class="font-semibold text-gray-800 text-lg">{{ $testimonio ? 'Editar mi opinión' : 'Deja tu opinión' }}</h2>

            @if($testimonio && $testimonio->estado !== 'pendiente')
                <p class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-xs rounded-xl px-4 py-3">Si editas tu opinión, volverá a revisión y dejará de mostrarse hasta que la doctora la apruebe de nuevo.</p>
            @endif

            {{-- Estrellas: radios accesibles --}}
            <fieldset>
                <legend class="text-sm font-medium text-gray-700 mb-2">Calificación</legend>
                <div class="flex gap-1">
                    @for($n = 1; $n <= 5; $n++)
                        <label class="relative cursor-pointer">
                            <input type="radio" name="calificacion" value="{{ $n }}" x-model.number="valor" class="peer sr-only" @checked((int) old('calificacion', $testimonio?->calificacion) === $n) required>
                            <span class="sr-only">{{ $n }} {{ $n === 1 ? 'estrella' : 'estrellas' }}</span>
                            <svg class="h-11 w-11 p-1.5 rounded-xl transition-colors duration-200 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-[#a13153]"
                                 :class="valor >= {{ $n }} ? 'text-[#d75078]' : 'text-gray-300'" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/></svg>
                        </label>
                    @endfor
                </div>
                @error('calificacion') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
            </fieldset>

            <div>
                <label for="texto" class="text-sm font-medium text-gray-700">Tu opinión</label>
                <textarea id="texto" name="texto" rows="5" x-model="texto" maxlength="{{ $max }}" required aria-describedby="texto-ayuda texto-contador"
                          class="mt-2 w-full rounded-xl border border-gray-200 bg-[#FFF6F6] px-4 py-3 text-sm text-gray-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#a13153]"></textarea>
                <div class="flex items-start justify-between gap-3 mt-1">
                    <p id="texto-ayuda" class="text-xs text-gray-500">De {{ $min }} a {{ $max }} caracteres. <strong class="font-semibold">No escribas datos médicos ni de contacto</strong> (teléfono, correo o enlaces): para eso comunícate con el consultorio. La doctora revisa cada opinión antes de publicarla.</p>
                    <p id="texto-contador" class="text-xs text-gray-500 shrink-0" x-text="[...texto].length + '/{{ $max }}'"></p>
                </div>
                @error('texto') <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="flex items-start gap-3 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="autorizo" value="1" required @checked(old('autorizo', $testimonio ? '1' : null))
                           class="mt-0.5 h-5 w-5 shrink-0 rounded border-gray-300 text-[#c03a63] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#a13153]">
                    <span>Autorizo que se muestre mi nombre y mi opinión en la página.</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-8">Solo se mostrará tu nombre de pila y la inicial de tu apellido.</p>
                @error('autorizo') <p class="text-red-500 text-xs mt-1 ml-8" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <button type="submit" class="min-h-11 bg-[#c03a63] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#a13153] transition-all duration-300">
                    {{ $testimonio ? 'Guardar cambios' : 'Enviar mi opinión' }}
                </button>
            </div>
        </form>

        @if($testimonio)
            <form method="POST" action="{{ route('paciente.testimonio.destroy') }}" class="max-w-2xl mt-4"
                  onsubmit="return confirm('¿Eliminar tu opinión? Esta acción no se puede deshacer.')">
                @csrf @method('DELETE')
                <button type="submit" class="min-h-11 text-sm font-medium text-red-600 hover:text-red-700 px-2 py-2 transition-colors duration-300">Eliminar mi opinión</button>
            </form>
        @endif
    @endif

</x-paciente.layout>
