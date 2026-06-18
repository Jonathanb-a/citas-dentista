<x-admin.layout title="Citas">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="font-bold text-gray-800 text-2xl">Gestión de citas</h1>
            <p class="font-light text-gray-400 text-sm mt-1">Administra todas las citas de la clínica</p>
        </div>
        <a href="{{ route('admin.citas.create') }}"
           class="bg-[#d75078] text-white text-sm font-medium px-5 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300 flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva cita
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('admin.citas.index') }}" class="flex flex-col md:flex-row gap-4 md:items-end">
            <div class="flex flex-col gap-1.5 flex-1">
                <label class="text-gray-600 text-sm font-medium">Filtrar por estado</label>
                <select name="status"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
                    <option value="">Todos</option>
                    <option value="pendiente" {{ request('status') === 'pendiente' ? 'selected' : '' }}>Pendientes</option>
                    <option value="confirmada" {{ request('status') === 'confirmada' ? 'selected' : '' }}>Confirmadas</option>
                    <option value="atendida" {{ request('status') === 'atendida' ? 'selected' : '' }}>Atendidas</option>
                    <option value="cancelada" {{ request('status') === 'cancelada' ? 'selected' : '' }}>Canceladas</option>
                </select>
            </div>
            <div class="flex flex-col gap-1.5 flex-1">
                <label class="text-gray-600 text-sm font-medium">Filtrar por fecha</label>
                <input type="date" name="fecha" value="{{ request('fecha') }}"
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="flex-1 md:flex-none bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                    Filtrar
                </button>
                <a href="{{ route('admin.citas.index') }}"
                   class="flex-1 md:flex-none text-center border border-gray-200 text-gray-500 text-sm font-medium px-6 py-3 rounded-xl hover:bg-gray-50 transition-all duration-300">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- Citas --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        @if($citas->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-400 text-sm">No hay citas que coincidan con los filtros</p>
            </div>
        @else
            {{-- Vista de tabla (desktop) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">FECHA</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">HORA</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">PACIENTE</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">SERVICIO</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">ESTADO</th>
                            <th class="text-right py-3 px-4 text-gray-400 text-xs font-medium">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($citas as $cita)
                        <tr class="border-b border-gray-50 hover:bg-[#FFF6F6] transition-colors duration-200">
                            <td class="py-4 px-4 text-gray-700 text-sm">{{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}</td>
                            <td class="py-4 px-4 text-gray-700 text-sm font-medium">{{ \Carbon\Carbon::parse($cita->hora)->format('h:i A') }}</td>
                            <td class="py-4 px-4">
                                <span class="text-gray-700 text-sm font-medium">{{ $cita->paciente->name }} {{ $cita->paciente->apellidos }}</span>
                                <p class="text-gray-400 text-xs">{{ $cita->paciente->telefono }}</p>
                            </td>
                            <td class="py-4 px-4 text-gray-500 text-sm">{{ $cita->servicio->nombre }}</td>
                            <td class="py-4 px-4">
                                @if($cita->status === 'pendiente')
                                    <span class="bg-yellow-50 text-yellow-600 text-xs font-medium px-3 py-1 rounded-full">Pendiente</span>
                                @elseif($cita->status === 'confirmada')
                                    <span class="bg-blue-50 text-blue-600 text-xs font-medium px-3 py-1 rounded-full">Confirmada</span>
                                @elseif($cita->status === 'atendida')
                                    <span class="bg-green-50 text-green-600 text-xs font-medium px-3 py-1 rounded-full">Atendida</span>
                                @elseif($cita->status === 'cancelada')
                                    <span class="bg-red-50 text-red-600 text-xs font-medium px-3 py-1 rounded-full">Cancelada</span>
                                @elseif($cita->status === 'reagendada')
                                    <span class="bg-purple-50 text-purple-600 text-xs font-medium px-3 py-1 rounded-full">Reagendada</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center justify-end gap-2">
                                    @if($cita->status === 'pendiente')
                                        <form method="POST" action="{{ route('admin.citas.confirmar', $cita->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-3 py-1.5 bg-blue-50 text-blue-600 text-xs font-medium rounded-lg hover:bg-blue-100 transition-all duration-200">Confirmar</button>
                                        </form>
                                    @endif
                                    @if(in_array($cita->status, ['pendiente', 'confirmada']))
                                        <form method="POST" action="{{ route('admin.citas.atender', $cita->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-3 py-1.5 bg-green-50 text-green-600 text-xs font-medium rounded-lg hover:bg-green-100 transition-all duration-200">Atender</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.citas.cancelar', $cita->id) }}" onsubmit="return confirm('¿Cancelar esta cita?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-3 py-1.5 bg-red-50 text-red-600 text-xs font-medium rounded-lg hover:bg-red-100 transition-all duration-200">Cancelar</button>
                                        </form>
                                    @endif
                                    @if(in_array($cita->status, ['atendida', 'cancelada']))
                                        <span class="text-gray-300 text-xs">Sin acciones</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Vista de tarjetas (móvil) --}}
            <div class="md:hidden flex flex-col gap-4">
                @foreach($citas as $cita)
                <div class="border border-gray-100 rounded-xl p-4 flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-gray-800 text-sm font-medium">{{ $cita->paciente->name }} {{ $cita->paciente->apellidos }}</p>
                            <p class="text-gray-400 text-xs">{{ $cita->paciente->telefono }}</p>
                        </div>
                        @if($cita->status === 'pendiente')
                            <span class="bg-yellow-50 text-yellow-600 text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap">Pendiente</span>
                        @elseif($cita->status === 'confirmada')
                            <span class="bg-blue-50 text-blue-600 text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap">Confirmada</span>
                        @elseif($cita->status === 'atendida')
                            <span class="bg-green-50 text-green-600 text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap">Atendida</span>
                        @elseif($cita->status === 'cancelada')
                            <span class="bg-red-50 text-red-600 text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap">Cancelada</span>
                        @elseif($cita->status === 'reagendada')
                            <span class="bg-purple-50 text-purple-600 text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap">Reagendada</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-4 text-sm text-gray-600">
                        <span>{{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}</span>
                        <span>{{ \Carbon\Carbon::parse($cita->hora)->format('h:i A') }}</span>
                    </div>
                    <p class="text-gray-500 text-sm">{{ $cita->servicio->nombre }}</p>

                    @if(!in_array($cita->status, ['atendida', 'cancelada']))
                        <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-50">
                            @if($cita->status === 'pendiente')
                                <form method="POST" action="{{ route('admin.citas.confirmar', $cita->id) }}" class="flex-1 min-w-[90px]">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="w-full px-3 py-2 bg-blue-50 text-blue-600 text-xs font-medium rounded-lg">Confirmar</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.citas.atender', $cita->id) }}" class="flex-1 min-w-[90px]">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="w-full px-3 py-2 bg-green-50 text-green-600 text-xs font-medium rounded-lg">Atender</button>
                            </form>
                            <form method="POST" action="{{ route('admin.citas.cancelar', $cita->id) }}" onsubmit="return confirm('¿Cancelar esta cita?');" class="flex-1 min-w-[90px]">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="w-full px-3 py-2 bg-red-50 text-red-600 text-xs font-medium rounded-lg">Cancelar</button>
                            </form>
                        </div>
                    @endif
                </div>
                @endforeach
            </div>
        @endif
    </div>

</x-admin.layout>