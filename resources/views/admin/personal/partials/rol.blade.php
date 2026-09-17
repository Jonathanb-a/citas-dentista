{{-- Distintivo de rol --}}
@if($role === 'admin')
    <span class="bg-[#fde7ed] text-[#d75078] text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap">Doctora</span>
@else
    <span class="bg-[#15626C]/10 text-[#15626C] text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap">Asistente</span>
@endif
