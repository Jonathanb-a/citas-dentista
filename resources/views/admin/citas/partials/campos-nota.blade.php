{{-- Campos de la nota de evolución. Se usan en el modal de atender y en el de editar --}}
@php
    $claseCampo = 'w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300 resize-y';
@endphp

<div class="flex flex-col gap-4">
    <div class="flex flex-col gap-1.5">
        <label class="text-gray-600 text-sm font-medium">Procedimiento</label>
        <textarea name="procedimiento" x-model="nota.campos.procedimiento" rows="3" maxlength="2000"
                  placeholder="Qué se le hizo en esta consulta"
                  class="{{ $claseCampo }}"></textarea>
    </div>
    <div class="flex flex-col gap-1.5">
        <label class="text-gray-600 text-sm font-medium">Observaciones</label>
        <textarea name="observaciones" x-model="nota.campos.observaciones" rows="3" maxlength="2000"
                  placeholder="Hallazgos, evolución, reacción del paciente"
                  class="{{ $claseCampo }}"></textarea>
    </div>
    <div class="flex flex-col gap-1.5">
        <label class="text-gray-600 text-sm font-medium">Indicaciones</label>
        <textarea name="indicaciones" x-model="nota.campos.indicaciones" rows="3" maxlength="2000"
                  placeholder="Qué debe hacer el paciente en casa"
                  class="{{ $claseCampo }}"></textarea>
    </div>
    <div class="flex flex-col gap-1.5">
        <label class="text-gray-600 text-sm font-medium">Próxima cita sugerida</label>
        <input type="text" name="proxima_cita_sugerida" x-model="nota.campos.proxima_cita_sugerida" maxlength="255"
               placeholder="Ej. en 15 días para revisión"
               class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:border-[#d75078] focus:ring-2 focus:ring-[#fde7ed] transition-all duration-300">
    </div>
</div>
