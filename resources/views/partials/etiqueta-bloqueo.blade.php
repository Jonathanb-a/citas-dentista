@if(in_array($cita->id, $citasBloqueadas ?? [], true))
    <span class="inline-block mt-1 bg-orange-50 text-orange-600 text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap"
          title="La agenda está bloqueada en este horario. Reagenda o cancela la cita.">En periodo bloqueado</span>
@endif
