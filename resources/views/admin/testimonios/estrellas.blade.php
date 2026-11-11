{{-- Estrellas de solo lectura: $calificacion (1 a 5). El lector de pantalla lee el número --}}
@php $calificacion = max(1, min(5, (int) $calificacion)); @endphp
<span class="inline-flex gap-0.5 text-[#d75078]" role="img" aria-label="Calificación: {{ $calificacion }} de 5">
    @for($e = 1; $e <= 5; $e++)
        <svg class="h-4 w-4 {{ $e <= $calificacion ? '' : 'text-gray-300' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/></svg>
    @endfor
</span>
