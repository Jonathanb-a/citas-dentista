{{-- Movimientos registrados de una cita (HistorialCita) --}}
<ul class="flex flex-col gap-2">
    @foreach($historial as $movimiento)
        <li class="flex items-start gap-3">
            <span class="w-2 h-2 rounded-full bg-[#d75078] mt-1.5 shrink-0"></span>
            <div class="min-w-0">
                <p class="text-gray-700 text-sm">
                    <span class="font-medium">{{ ucfirst($movimiento->accion) }}</span>
                    <span class="text-gray-400 text-xs">· {{ $movimiento->created_at?->format('d/m/Y h:i A') }} · {{ $movimiento->user?->name ?? 'Sistema' }}</span>
                </p>
                @if($movimiento->descripcion)
                    <p class="text-gray-500 text-xs">{{ $movimiento->descripcion }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ul>
