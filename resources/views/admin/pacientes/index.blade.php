<x-admin.layout title="Pacientes">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="font-bold text-gray-800 text-2xl">Gestión de pacientes</h1>
            <p class="font-light text-gray-400 text-sm mt-1">Consulta y administra a los pacientes de la clínica</p>
        </div>
        <span class="text-gray-400 text-sm">{{ $pacientes->total() }} {{ $pacientes->total() === 1 ? 'paciente' : 'pacientes' }}</span>
    </div>

    {{-- Buscador --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('admin.pacientes.index') }}" class="flex flex-col md:flex-row gap-4 md:items-end">
            <div class="flex flex-col gap-1.5 flex-1">
                <label class="text-gray-600 text-sm font-medium">Buscar paciente</label>
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                       placeholder="Nombre, apellidos, correo o teléfono"
                       class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
            </div>
            <div class="flex gap-2">
                <button type="submit"
                        class="flex-1 md:flex-none bg-[#d75078] text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                    Buscar
                </button>
                <a href="{{ route('admin.pacientes.index') }}"
                   class="flex-1 md:flex-none text-center border border-gray-200 text-gray-500 text-sm font-medium px-6 py-3 rounded-xl hover:bg-gray-50 transition-all duration-300">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- Pacientes --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        @if($pacientes->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-gray-400 text-sm">
                    {{ request()->filled('buscar') ? 'No hay pacientes que coincidan con la búsqueda' : 'No hay pacientes registrados todavía' }}
                </p>
            </div>
        @else
            {{-- Vista de tabla (desktop) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">PACIENTE</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">CONTACTO</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">CITAS</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">ESTADO</th>
                            <th class="text-right py-3 px-4 text-gray-400 text-xs font-medium">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pacientes as $paciente)
                        <tr class="border-b border-gray-50 hover:bg-[#FFF6F6] transition-colors duration-200">
                            <td class="py-4 px-4">
                                <a href="{{ route('admin.pacientes.show', $paciente->id) }}" class="text-gray-700 text-sm font-medium hover:text-[#d75078] transition-colors duration-200">
                                    {{ $paciente->name }} {{ $paciente->apellidos }}
                                </a>
                                <p class="text-gray-400 text-xs">Registrado el {{ $paciente->created_at?->format('d/m/Y') }}</p>
                            </td>
                            <td class="py-4 px-4">
                                <p class="text-gray-700 text-sm">{{ $paciente->email }}</p>
                                <p class="text-gray-400 text-xs">{{ $paciente->telefono ?? 'Sin teléfono' }}</p>
                            </td>
                            <td class="py-4 px-4 text-gray-500 text-sm">{{ $paciente->citas_count }}</td>
                            <td class="py-4 px-4">
                                @if($paciente->activo)
                                    <span class="bg-green-50 text-green-600 text-xs font-medium px-3 py-1 rounded-full">Activo</span>
                                @else
                                    <span class="bg-red-50 text-red-600 text-xs font-medium px-3 py-1 rounded-full">Inactivo</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.pacientes.show', $paciente->id) }}"
                                       class="px-3 py-1.5 bg-[#fde7ed] text-[#d75078] text-xs font-medium rounded-lg hover:bg-[#fbd3df] transition-all duration-200">
                                        Ver
                                    </a>
                                    <form method="POST" action="{{ route('admin.pacientes.toggle', $paciente->id) }}"
                                          onsubmit="return confirm('{{ $paciente->activo ? '¿Desactivar a este paciente? Ya no podrá iniciar sesión.' : '¿Activar a este paciente?' }}');">
                                        @csrf
                                        @method('PATCH')
                                        @if($paciente->activo)
                                            <button type="submit" class="px-3 py-1.5 bg-red-50 text-red-600 text-xs font-medium rounded-lg hover:bg-red-100 transition-all duration-200">Desactivar</button>
                                        @else
                                            <button type="submit" class="px-3 py-1.5 bg-green-50 text-green-600 text-xs font-medium rounded-lg hover:bg-green-100 transition-all duration-200">Activar</button>
                                        @endif
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Vista de tarjetas (móvil) --}}
            <div class="md:hidden flex flex-col gap-4">
                @foreach($pacientes as $paciente)
                <div class="border border-gray-100 rounded-xl p-4 flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-gray-800 text-sm font-medium">{{ $paciente->name }} {{ $paciente->apellidos }}</p>
                            <p class="text-gray-400 text-xs break-all">{{ $paciente->email }}</p>
                        </div>
                        @if($paciente->activo)
                            <span class="bg-green-50 text-green-600 text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap">Activo</span>
                        @else
                            <span class="bg-red-50 text-red-600 text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap">Inactivo</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-4 text-sm text-gray-600">
                        <span>{{ $paciente->telefono ?? 'Sin teléfono' }}</span>
                        <span>{{ $paciente->citas_count }} {{ $paciente->citas_count === 1 ? 'cita' : 'citas' }}</span>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-50">
                        <a href="{{ route('admin.pacientes.show', $paciente->id) }}"
                           class="flex-1 min-w-[90px] text-center px-3 py-2 bg-[#fde7ed] text-[#d75078] text-xs font-medium rounded-lg">
                            Ver
                        </a>
                        <form method="POST" action="{{ route('admin.pacientes.toggle', $paciente->id) }}" class="flex-1 min-w-[90px]"
                              onsubmit="return confirm('{{ $paciente->activo ? '¿Desactivar a este paciente? Ya no podrá iniciar sesión.' : '¿Activar a este paciente?' }}');">
                            @csrf
                            @method('PATCH')
                            @if($paciente->activo)
                                <button type="submit" class="w-full px-3 py-2 bg-red-50 text-red-600 text-xs font-medium rounded-lg">Desactivar</button>
                            @else
                                <button type="submit" class="w-full px-3 py-2 bg-green-50 text-green-600 text-xs font-medium rounded-lg">Activar</button>
                            @endif
                        </form>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Paginación --}}
            @if($pacientes->hasPages())
                <div class="flex items-center justify-between gap-4 pt-6 mt-2 border-t border-gray-50">
                    @if($pacientes->onFirstPage())
                        <span class="px-4 py-2 border border-gray-100 text-gray-300 text-sm font-medium rounded-xl cursor-not-allowed">Anterior</span>
                    @else
                        <a href="{{ $pacientes->previousPageUrl() }}"
                           class="px-4 py-2 border border-gray-200 text-gray-500 text-sm font-medium rounded-xl hover:bg-[#fde7ed] hover:text-[#d75078] transition-all duration-300">Anterior</a>
                    @endif

                    <span class="text-gray-400 text-sm">Página {{ $pacientes->currentPage() }} de {{ $pacientes->lastPage() }}</span>

                    @if($pacientes->hasMorePages())
                        <a href="{{ $pacientes->nextPageUrl() }}"
                           class="px-4 py-2 border border-gray-200 text-gray-500 text-sm font-medium rounded-xl hover:bg-[#fde7ed] hover:text-[#d75078] transition-all duration-300">Siguiente</a>
                    @else
                        <span class="px-4 py-2 border border-gray-100 text-gray-300 text-sm font-medium rounded-xl cursor-not-allowed">Siguiente</span>
                    @endif
                </div>
            @endif
        @endif
    </div>

</x-admin.layout>
