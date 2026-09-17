@php $clases = $clases ?? ''; @endphp
@if($status === 'pendiente')
    <span class="bg-yellow-50 text-yellow-600 text-xs font-medium px-3 py-1 rounded-full {{ $clases }}">Pendiente</span>
@elseif($status === 'confirmada')
    <span class="bg-blue-50 text-blue-600 text-xs font-medium px-3 py-1 rounded-full {{ $clases }}">Confirmada</span>
@elseif($status === 'atendida')
    <span class="bg-green-50 text-green-600 text-xs font-medium px-3 py-1 rounded-full {{ $clases }}">Atendida</span>
@elseif($status === 'cancelada')
    <span class="bg-red-50 text-red-600 text-xs font-medium px-3 py-1 rounded-full {{ $clases }}">Cancelada</span>
@elseif($status === 'reagendada')
    <span class="bg-purple-50 text-purple-600 text-xs font-medium px-3 py-1 rounded-full {{ $clases }}">Reagendada</span>
@endif
