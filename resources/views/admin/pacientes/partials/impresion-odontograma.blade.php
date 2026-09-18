{{-- Odontograma para papel: color + letra + trama, para que se lea en blanco y negro --}}
@php
    use App\Models\Odontograma;

    $tramas = [
        'diagonal' => '<path d="M0,6 l6,-6 M-1,1 l2,-2 M5,7 l2,-2" stroke="#111827" stroke-width="1"/>',
        'contradiagonal' => '<path d="M0,0 l6,6 M-1,5 l2,2 M5,-1 l2,2" stroke="#111827" stroke-width="1"/>',
        'vertical' => '<path d="M2,0 v6 M5,0 v6" stroke="#111827" stroke-width="1"/>',
        'horizontal' => '<path d="M0,2 h6 M0,5 h6" stroke="#111827" stroke-width="1"/>',
        'cuadricula' => '<path d="M0,3 h6 M3,0 v6" stroke="#111827" stroke-width="0.8"/>',
        'puntos' => '<circle cx="2" cy="2" r="1" fill="#111827"/>',
        'puntos-grandes' => '<circle cx="3" cy="3" r="1.6" fill="#111827"/>',
        'solido' => '<rect width="6" height="6" fill="#111827"/>',
    ];
@endphp

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <defs>
        @foreach($tramas as $nombre => $dibujo)
            <pattern id="trama-{{ $nombre }}" width="6" height="6" patternUnits="userSpaceOnUse">
                {!! $dibujo !!}
            </pattern>
        @endforeach
    </defs>
</svg>

@foreach([
    'ARCADA SUPERIOR' => ['superior_derecho', 'superior_izquierdo'],
    'ARCADA INFERIOR' => ['inferior_derecho', 'inferior_izquierdo'],
] as $titulo => $cuadrantes)
    <div class="arcada">
        <p class="arcada-titulo">{{ $titulo }}</p>
        <div class="dientes">
            @foreach($cuadrantes as $indice => $cuadrante)
                @foreach(Odontograma::CUADRANTES[$cuadrante] as $numero)
                    @php
                        $registro = $dientesMarcados->get($numero);
                        $estado = $registro->estado ?? 'sano';
                        $datos = Odontograma::ESTADOS[$estado];
                    @endphp
                    <div class="diente {{ $loop->first && $indice === 1 ? 'diente-media' : '' }}">
                        <svg viewBox="0 0 24 24" class="diente-figura">
                            <rect x="1" y="1" width="22" height="22" rx="4"
                                  fill="{{ $datos['relleno'] }}" stroke="#111827" stroke-width="1"/>
                            @if($datos['trama'])
                                <rect x="1" y="1" width="22" height="22" rx="4"
                                      fill="url(#trama-{{ $datos['trama'] }})" opacity="0.45"/>
                            @endif
                            @if($datos['letra'])
                                <text x="12" y="17" text-anchor="middle" font-size="13" font-weight="bold"
                                      fill="{{ $datos['textoClaro'] ? '#ffffff' : '#111827' }}"
                                      stroke="{{ $datos['textoClaro'] ? '#111827' : 'none' }}" stroke-width="0.4">{{ $datos['letra'] }}</text>
                            @endif
                        </svg>
                        <span class="diente-numero">{{ $numero }}</span>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
@endforeach

{{-- Leyenda: letra, trama y nombre --}}
<div class="leyenda">
    @foreach(Odontograma::ESTADOS as $clave => $estado)
        <span class="leyenda-item">
            <svg viewBox="0 0 24 24" class="leyenda-figura">
                <rect x="1" y="1" width="22" height="22" rx="4" fill="{{ $estado['relleno'] }}" stroke="#111827" stroke-width="1"/>
                @if($estado['trama'])
                    <rect x="1" y="1" width="22" height="22" rx="4" fill="url(#trama-{{ $estado['trama'] }})" opacity="0.45"/>
                @endif
                @if($estado['letra'])
                    <text x="12" y="17" text-anchor="middle" font-size="13" font-weight="bold"
                          fill="{{ $estado['textoClaro'] ? '#ffffff' : '#111827' }}"
                          stroke="{{ $estado['textoClaro'] ? '#111827' : 'none' }}" stroke-width="0.4">{{ $estado['letra'] }}</text>
                @endif
            </svg>
            {{ $estado['letra'] ? $estado['letra'] . ' = ' : '' }}{{ $estado['etiqueta'] }}
        </span>
    @endforeach
</div>

{{-- Notas por diente --}}
@if($dientesMarcados->filter(fn ($d) => filled($d->nota))->isNotEmpty())
    <table class="tabla tabla-notas-diente">
        <thead>
            <tr><th>DIENTE</th><th>ESTADO</th><th>NOTA</th></tr>
        </thead>
        <tbody>
            @foreach($dientesMarcados->sortBy('numero_diente') as $diente)
                @if(filled($diente->nota))
                    <tr>
                        <td>{{ $diente->numero_diente }}</td>
                        <td>{{ Odontograma::etiqueta($diente->estado) }}</td>
                        <td>{{ $diente->nota }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
@endif
