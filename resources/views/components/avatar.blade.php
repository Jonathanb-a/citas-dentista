{{--
    Avatar circular del paciente: su foto o, sin foto (o si la imagen no carga), sus iniciales.
    <x-avatar :usuario="$paciente" tamano="h-10 w-10" texto="text-sm" />
    Todo se escapa con Blade; la URL sale de ImagenService (nunca de texto del usuario).
--}}
@props(['usuario', 'tamano' => 'h-10 w-10', 'texto' => 'text-sm'])
@php
    $url = $usuario->fotoUrl();
    $clases = 'relative inline-flex shrink-0 select-none items-center justify-center overflow-hidden rounded-full bg-[#fde7ed] font-bold text-[#a13153] ' . $tamano . ' ' . $texto;
@endphp
@if($url)
    <span {{ $attributes->merge(['class' => $clases]) }} x-data="{ falla: false }">
        <span aria-hidden="true" x-show="falla" style="display: none">{{ $usuario->iniciales() }}</span>
        <img src="{{ $url }}" alt="" loading="lazy" decoding="async" x-on:error="falla = true" x-show="!falla"
             class="absolute inset-0 h-full w-full object-cover">
    </span>
@else
    <span {{ $attributes->merge(['class' => $clases]) }}><span aria-hidden="true">{{ $usuario->iniciales() }}</span></span>
@endif
