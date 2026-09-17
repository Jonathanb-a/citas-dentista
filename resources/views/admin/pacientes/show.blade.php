<x-admin.layout title="Paciente">

    <div x-data="{ modalAbierto: @json($errors->any()) }">

    @php
        $expediente = $paciente->expediente;
        $fechaNacimiento = $paciente->fecha_nacimiento ?? $expediente?->fecha_nacimiento;
        $sexo = $paciente->sexo ?? $expediente?->sexo;
    @endphp

    {{-- Header --}}
    <div class="mb-8">
        <a href="{{ route('admin.pacientes.index') }}"
           class="inline-flex items-center gap-1 text-gray-400 text-sm hover:text-[#d75078] transition-colors duration-200 mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a pacientes
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="font-bold text-gray-800 text-2xl">{{ $paciente->name }} {{ $paciente->apellidos }}</h1>
                    @if($paciente->activo)
                        <span class="bg-green-50 text-green-600 text-xs font-medium px-3 py-1 rounded-full">Activo</span>
                    @else
                        <span class="bg-red-50 text-red-600 text-xs font-medium px-3 py-1 rounded-full">Inactivo</span>
                    @endif
                </div>
                <p class="font-light text-gray-400 text-sm mt-1">Datos, expediente e historial de citas del paciente</p>
            </div>
            <div class="flex gap-2">
                <button @click="modalAbierto = true"
                        class="flex-1 sm:flex-none bg-[#d75078] text-white text-sm font-medium px-5 py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300 flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editar
                </button>
                <form method="POST" action="{{ route('admin.pacientes.toggle', $paciente->id) }}" class="flex-1 sm:flex-none"
                      onsubmit="return confirm('{{ $paciente->activo ? '¿Desactivar a este paciente? Ya no podrá iniciar sesión.' : '¿Activar a este paciente?' }}');">
                    @csrf
                    @method('PATCH')
                    @if($paciente->activo)
                        <button type="submit" class="w-full border border-red-200 text-red-600 text-sm font-medium px-5 py-3 rounded-xl hover:bg-red-50 transition-all duration-300">Desactivar</button>
                    @else
                        <button type="submit" class="w-full border border-green-200 text-green-600 text-sm font-medium px-5 py-3 rounded-xl hover:bg-green-50 transition-all duration-300">Activar</button>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        {{-- Datos personales --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-bold text-gray-800 text-lg mb-4">Datos personales</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <dt class="text-gray-400 text-xs font-medium">CORREO</dt>
                    <dd class="text-gray-700 text-sm mt-1 break-all">{{ $paciente->email }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs font-medium">TELÉFONO</dt>
                    <dd class="text-gray-700 text-sm mt-1">{{ $paciente->telefono ?? 'Sin registrar' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs font-medium">FECHA DE NACIMIENTO</dt>
                    <dd class="text-gray-700 text-sm mt-1">
                        @if($fechaNacimiento)
                            {{ $fechaNacimiento->format('d/m/Y') }}
                            <span class="text-gray-400">({{ (int) $fechaNacimiento->diffInYears(now()) }} años)</span>
                        @else
                            Sin registrar
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs font-medium">SEXO</dt>
                    <dd class="text-gray-700 text-sm mt-1">{{ $sexo ? ucfirst($sexo) : 'Sin registrar' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs font-medium">REGISTRADO EL</dt>
                    <dd class="text-gray-700 text-sm mt-1">{{ $paciente->created_at?->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs font-medium">CORREO VERIFICADO</dt>
                    <dd class="text-gray-700 text-sm mt-1">{{ $paciente->email_verified_at ? 'Sí' : 'No' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Resumen de citas --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-bold text-gray-800 text-lg mb-4">Resumen de citas</h2>
            <div class="grid grid-cols-3 lg:grid-cols-1 gap-3">
                <div class="bg-[#FFF6F6] rounded-xl p-3 text-center">
                    <p class="text-[#d75078] text-xl font-bold">{{ $resumen['total'] }}</p>
                    <p class="text-gray-400 text-xs">Citas</p>
                </div>
                <div class="bg-green-50 rounded-xl p-3 text-center">
                    <p class="text-green-600 text-xl font-bold">{{ $resumen['atendidas'] }}</p>
                    <p class="text-gray-400 text-xs">Atendidas</p>
                </div>
                <div class="bg-red-50 rounded-xl p-3 text-center">
                    <p class="text-red-600 text-xl font-bold">{{ $resumen['canceladas'] }}</p>
                    <p class="text-gray-400 text-xs">Canceladas</p>
                </div>
            </div>
        </div>

    </div>

    {{-- Expediente clínico --}}
    @include('admin.pacientes.partials.expediente-form')

    {{-- Historial de citas --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h2 class="font-bold text-gray-800 text-lg mb-4">Historial de citas</h2>

        @if($paciente->citas->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-400 text-sm">Este paciente no tiene citas registradas</p>
            </div>
        @else
            {{-- Vista de tabla (desktop) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">FECHA</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">HORA</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">SERVICIO</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">MOTIVO</th>
                            <th class="text-left py-3 px-4 text-gray-400 text-xs font-medium">ESTADO</th>
                            <th class="text-right py-3 px-4 text-gray-400 text-xs font-medium">MOVIMIENTOS</th>
                        </tr>
                    </thead>
                    @foreach($paciente->citas as $cita)
                    <tbody x-data="{ abierto: false }">
                        <tr class="border-b border-gray-50 hover:bg-[#FFF6F6] transition-colors duration-200">
                            <td class="py-4 px-4 text-gray-700 text-sm">{{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}</td>
                            <td class="py-4 px-4 text-gray-700 text-sm font-medium">{{ \Carbon\Carbon::parse($cita->hora)->format('h:i A') }}</td>
                            <td class="py-4 px-4">
                                <span class="text-gray-700 text-sm">{{ $cita->servicio->nombre ?? '—' }}</span>
                                <p class="text-gray-400 text-xs">{{ $cita->servicio->especialidad->nombre ?? '' }}</p>
                            </td>
                            <td class="py-4 px-4 text-gray-500 text-sm max-w-xs truncate" title="{{ $cita->motivo }}">{{ $cita->motivo ?: '—' }}</td>
                            <td class="py-4 px-4">
                                @include('admin.pacientes.partials.estado-cita', ['status' => $cita->status])
                            </td>
                            <td class="py-4 px-4 text-right">
                                @if($cita->historial->isNotEmpty())
                                    <button @click="abierto = !abierto"
                                            class="px-3 py-1.5 bg-[#fde7ed] text-[#d75078] text-xs font-medium rounded-lg hover:bg-[#fbd3df] transition-all duration-200"
                                            x-text="abierto ? 'Ocultar' : 'Ver ({{ $cita->historial->count() }})'">
                                    </button>
                                @else
                                    <span class="text-gray-300 text-xs">Sin movimientos</span>
                                @endif
                            </td>
                        </tr>
                        @if($cita->historial->isNotEmpty())
                            <tr x-show="abierto" x-cloak class="border-b border-gray-50 bg-[#FFF6F6]">
                                <td colspan="6" class="px-4 py-3">
                                    @include('admin.pacientes.partials.historial-cita', ['historial' => $cita->historial])
                                </td>
                            </tr>
                        @endif
                    </tbody>
                    @endforeach
                </table>
            </div>

            {{-- Vista de tarjetas (móvil) --}}
            <div class="md:hidden flex flex-col gap-4">
                @foreach($paciente->citas as $cita)
                <div x-data="{ abierto: false }" class="border border-gray-100 rounded-xl p-4 flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-gray-800 text-sm font-medium">{{ $cita->servicio->nombre ?? '—' }}</p>
                            <p class="text-gray-400 text-xs">{{ $cita->servicio->especialidad->nombre ?? '' }}</p>
                        </div>
                        @include('admin.pacientes.partials.estado-cita', ['status' => $cita->status, 'clases' => 'whitespace-nowrap'])
                    </div>

                    <div class="flex items-center gap-4 text-sm text-gray-600">
                        <span>{{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}</span>
                        <span>{{ \Carbon\Carbon::parse($cita->hora)->format('h:i A') }}</span>
                    </div>
                    @if($cita->motivo)
                        <p class="text-gray-500 text-sm">{{ $cita->motivo }}</p>
                    @endif

                    @if($cita->historial->isNotEmpty())
                        <div class="pt-2 border-t border-gray-50">
                            <button @click="abierto = !abierto"
                                    class="w-full px-3 py-2 bg-[#fde7ed] text-[#d75078] text-xs font-medium rounded-lg"
                                    x-text="abierto ? 'Ocultar movimientos' : 'Ver movimientos ({{ $cita->historial->count() }})'">
                            </button>
                            <div x-show="abierto" x-cloak class="mt-3">
                                @include('admin.pacientes.partials.historial-cita', ['historial' => $cita->historial])
                            </div>
                        </div>
                    @endif
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Modal de edición --}}
    <div x-show="modalAbierto" x-cloak
         class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
         @click.self="modalAbierto = false">
        <div x-show="modalAbierto"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-lg w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">

            <div class="flex items-center justify-between mb-6">
                <h2 class="font-bold text-gray-800 text-lg">Editar paciente</h2>
                <button @click="modalAbierto = false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-4">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.pacientes.update', $paciente->id) }}" class="flex flex-col gap-4">
                @csrf
                @method('PUT')

                {{-- Nombre --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Nombre</label>
                    <input type="text" name="name" value="{{ old('name', $paciente->name) }}" required maxlength="255"
                           class="w-full px-4 py-3 rounded-xl border text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300 {{ $errors->has('name') ? 'border-red-300' : 'border-gray-200' }}">
                </div>

                {{-- Apellidos --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Apellidos</label>
                    <input type="text" name="apellidos" value="{{ old('apellidos', $paciente->apellidos) }}" required maxlength="255"
                           class="w-full px-4 py-3 rounded-xl border text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300 {{ $errors->has('apellidos') ? 'border-red-300' : 'border-gray-200' }}">
                </div>

                {{-- Correo --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Correo electrónico</label>
                    <input type="email" name="email" value="{{ old('email', $paciente->email) }}" required maxlength="255"
                           class="w-full px-4 py-3 rounded-xl border text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300 {{ $errors->has('email') ? 'border-red-300' : 'border-gray-200' }}">
                </div>

                {{-- Teléfono --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-gray-600 text-sm font-medium">Teléfono</label>
                    <input type="tel" name="telefono" value="{{ old('telefono', $paciente->telefono) }}" maxlength="20"
                           placeholder="Ej. 5512345678"
                           class="w-full px-4 py-3 rounded-xl border text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300 {{ $errors->has('telefono') ? 'border-red-300' : 'border-gray-200' }}">
                </div>

                {{-- Botones --}}
                <div class="flex gap-3 mt-2">
                    <button type="button" @click="modalAbierto = false"
                            class="flex-1 border border-gray-200 text-gray-500 text-sm font-medium py-3 rounded-xl hover:bg-gray-50 transition-all duration-300">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 bg-[#d75078] text-white text-sm font-medium py-3 rounded-xl hover:bg-[#f285a2] transition-all duration-300">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    </div>

</x-admin.layout>
