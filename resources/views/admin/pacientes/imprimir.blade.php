{{-- Expediente para papel: vista independiente, sin sidebar ni botones --}}
@php
    use App\Models\Odontograma;

    $consultorio = config('consultorio');
    $expediente = $paciente->expediente;
    $dientesMarcados = $expediente ? $expediente->odontograma->keyBy('numero_diente') : collect();
    $fechaNacimiento = $paciente->fecha_nacimiento ?? $expediente?->fecha_nacimiento;
    $sexo = $paciente->sexo ?? $expediente?->sexo;

    $siNo = fn ($valor) => is_null($valor) ? 'Sin dato' : ($valor ? 'Sí' : 'No');
    $texto = fn ($valor) => filled($valor) ? $valor : 'Sin información';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expediente — {{ $paciente->name }} {{ $paciente->apellidos }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page { size: letter; margin: 1.5cm; }

        * { box-sizing: border-box; }

        body {
            font-family: 'Montserrat', Arial, sans-serif;
            color: #111827;
            font-size: 10.5pt;
            line-height: 1.45;
            margin: 0;
            padding: 1.5cm;
            background: #ffffff;
        }

        /* En pantalla se ve como una hoja; al imprimir, la hoja es la hoja */
        @media screen {
            body { max-width: 21.6cm; margin: 1cm auto; box-shadow: 0 2px 18px rgba(0,0,0,.12); }
        }
        @media print {
            body { padding: 0; box-shadow: none; }
            /* Conserva rellenos y tramas si la impresora tiene color */
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }

        h1, h2, h3, p, table { margin: 0; }

        .encabezado {
            display: flex; justify-content: space-between; align-items: flex-start; gap: 1cm;
            border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 16px;
        }
        .consultorio-nombre { font-size: 15pt; font-weight: 700; }
        .consultorio-dato { font-size: 9pt; color: #374151; }
        .encabezado-derecha { text-align: right; font-size: 9pt; color: #374151; }
        .documento-titulo { font-size: 11pt; font-weight: 700; color: #111827; }

        .seccion { margin-bottom: 14px; break-inside: avoid; page-break-inside: avoid; }
        .seccion-titulo {
            font-size: 10pt; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
            border-bottom: 1px solid #9ca3af; padding-bottom: 3px; margin-bottom: 8px;
        }

        .campos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px 14px; }
        .campos-2 { grid-template-columns: repeat(2, 1fr); }
        .campo-etiqueta { font-size: 7.5pt; text-transform: uppercase; color: #6b7280; letter-spacing: .03em; }
        .campo-valor { font-size: 10pt; }
        .bloque { margin-bottom: 8px; }
        .bloque-texto { white-space: pre-line; }

        .tabla { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        .tabla th {
            text-align: left; font-size: 7.5pt; text-transform: uppercase; color: #374151;
            border-bottom: 1px solid #9ca3af; padding: 4px 6px;
        }
        .tabla td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .tabla-notas-diente { margin-top: 10px; }

        /* Odontograma */
        .arcada { margin-bottom: 10px; }
        .arcada-titulo { font-size: 7.5pt; color: #6b7280; text-align: center; margin-bottom: 4px; letter-spacing: .04em; }
        .dientes { display: flex; justify-content: center; flex-wrap: nowrap; gap: 2px; }
        .diente { width: 38px; text-align: center; }
        .diente-media { margin-left: 10px; }
        .diente-figura { width: 26px; height: 26px; display: block; margin: 0 auto; }
        .diente-numero { font-size: 7pt; color: #374151; }

        .leyenda { display: flex; flex-wrap: wrap; gap: 4px 14px; margin-top: 10px; font-size: 8pt; }
        .leyenda-item { display: flex; align-items: center; gap: 4px; }
        .leyenda-figura { width: 14px; height: 14px; }

        .nota-evolucion { border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 10px; margin-bottom: 8px; break-inside: avoid; }
        .nota-titulo { font-weight: 600; font-size: 10pt; margin-bottom: 4px; }
        .nota-autor { font-size: 8pt; color: #6b7280; margin-top: 6px; }

        .firma { margin-top: 40px; break-inside: avoid; text-align: center; }
        .firma-linea { width: 8cm; border-top: 1px solid #111827; margin: 0 auto 4px; }
        .firma-nombre { font-size: 10pt; font-weight: 600; }
        .firma-rol { font-size: 8.5pt; color: #374151; }

        .pie { margin-top: 18px; padding-top: 6px; border-top: 1px solid #e5e7eb; font-size: 7.5pt; color: #6b7280; text-align: center; }
        .vacio { color: #9ca3af; }
    </style>
</head>
<body onload="window.print()">

    {{-- Encabezado --}}
    <div class="encabezado">
        <div>
            <p class="consultorio-nombre">{{ $consultorio['nombre'] }}</p>
            @if($consultorio['subtitulo'])
                <p class="consultorio-dato">{{ $consultorio['subtitulo'] }}@if($consultorio['cedula']) · Céd. Prof. {{ $consultorio['cedula'] }}@endif</p>
            @endif
            <p class="consultorio-dato">{{ $consultorio['direccion'] }}</p>
            @if($consultorio['telefono'] || $consultorio['correo'])
                <p class="consultorio-dato">
                    {{ $consultorio['telefono'] }}@if($consultorio['telefono'] && $consultorio['correo']) · @endif{{ $consultorio['correo'] }}
                </p>
            @endif
        </div>
        <div class="encabezado-derecha">
            <p class="documento-titulo">Expediente clínico</p>
            <p>Impreso el {{ now()->format('d/m/Y h:i A') }}</p>
        </div>
    </div>

    {{-- Ficha de identificación --}}
    <div class="seccion">
        <p class="seccion-titulo">Ficha de identificación</p>
        <div class="campos">
            <div><p class="campo-etiqueta">Nombre</p><p class="campo-valor">{{ $paciente->name }} {{ $paciente->apellidos }}</p></div>
            <div><p class="campo-etiqueta">Fecha de nacimiento</p><p class="campo-valor">{{ $fechaNacimiento ? $fechaNacimiento->format('d/m/Y') . ' (' . (int) $fechaNacimiento->diffInYears(now()) . ' años)' : 'Sin dato' }}</p></div>
            <div><p class="campo-etiqueta">Sexo</p><p class="campo-valor">{{ $sexo ? ucfirst($sexo) : 'Sin dato' }}</p></div>
            <div><p class="campo-etiqueta">Teléfono</p><p class="campo-valor">{{ $paciente->telefono ?? 'Sin dato' }}</p></div>
            <div><p class="campo-etiqueta">Correo</p><p class="campo-valor">{{ $paciente->email }}</p></div>
            <div><p class="campo-etiqueta">Tipo de sangre</p><p class="campo-valor">{{ $expediente?->tipo_sangre ?? 'Sin dato' }}</p></div>
            <div style="grid-column: span 2"><p class="campo-etiqueta">Dirección</p><p class="campo-valor">{{ $expediente?->direccion ?? 'Sin dato' }}</p></div>
            <div><p class="campo-etiqueta">Contacto de emergencia</p><p class="campo-valor">{{ $expediente?->contacto_emergencia_nombre ?? 'Sin dato' }}{{ $expediente?->contacto_emergencia_telefono ? ' · ' . $expediente->contacto_emergencia_telefono : '' }}</p></div>
        </div>
    </div>

    {{-- Antecedentes --}}
    <div class="seccion">
        <p class="seccion-titulo">Antecedentes</p>
        @foreach([
            'antecedentes_familiares' => 'Heredofamiliares',
            'alergias' => 'Alergias',
            'enfermedades' => 'Enfermedades',
            'medicamentos' => 'Medicamentos',
            'cirugias_previas' => 'Cirugías previas',
        ] as $campo => $etiqueta)
            <div class="bloque">
                <p class="campo-etiqueta">{{ $etiqueta }}</p>
                <p class="campo-valor bloque-texto">{{ $texto($expediente?->$campo) }}</p>
            </div>
        @endforeach
        @if($sexo !== 'masculino')
            <div class="bloque">
                <p class="campo-etiqueta">Embarazada</p>
                <p class="campo-valor">{{ $siNo($expediente?->embarazada) }}</p>
            </div>
        @endif
    </div>

    {{-- Hábitos --}}
    <div class="seccion">
        <p class="seccion-titulo">Hábitos</p>
        <div class="campos">
            <div><p class="campo-etiqueta">Fuma</p><p class="campo-valor">{{ $siNo($expediente?->fuma) }}</p></div>
            <div><p class="campo-etiqueta">Consume alcohol</p><p class="campo-valor">{{ $siNo($expediente?->alcohol) }}</p></div>
            <div><p class="campo-etiqueta">Bruxismo</p><p class="campo-valor">{{ $siNo($expediente?->bruxismo) }}</p></div>
            <div><p class="campo-etiqueta">Frecuencia de cepillado</p><p class="campo-valor">{{ $expediente?->frecuencia_cepillado ?? 'Sin dato' }}</p></div>
            <div><p class="campo-etiqueta">Usa hilo dental</p><p class="campo-valor">{{ $siNo($expediente?->usa_hilo_dental) }}</p></div>
        </div>
    </div>

    {{-- Padecimiento, diagnóstico y plan --}}
    <div class="seccion">
        <p class="seccion-titulo">Padecimiento actual, diagnóstico y plan de tratamiento</p>
        @foreach([
            'padecimiento_actual' => 'Padecimiento actual',
            'diagnostico' => 'Diagnóstico',
            'plan_tratamiento' => 'Plan de tratamiento',
            'notas_doctora' => 'Notas de la doctora',
            'observaciones' => 'Observaciones',
        ] as $campo => $etiqueta)
            <div class="bloque">
                <p class="campo-etiqueta">{{ $etiqueta }}</p>
                <p class="campo-valor bloque-texto">{{ $texto($expediente?->$campo) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Odontograma --}}
    <div class="seccion">
        <p class="seccion-titulo">Odontograma</p>
        @include('admin.pacientes.partials.impresion-odontograma')
    </div>

    {{-- Historial de citas --}}
    <div class="seccion">
        <p class="seccion-titulo">Historial de citas</p>
        @if($paciente->citas->isEmpty())
            <p class="campo-valor vacio">Sin citas registradas.</p>
        @else
            <table class="tabla">
                <thead>
                    <tr><th>FECHA</th><th>HORA</th><th>SERVICIO</th><th>MOTIVO</th><th>ESTADO</th></tr>
                </thead>
                <tbody>
                    @foreach($paciente->citas as $cita)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($cita->hora)->format('h:i A') }}</td>
                            <td>{{ $cita->servicio->nombre ?? '—' }}</td>
                            <td>{{ $cita->motivo ?: '—' }}</td>
                            <td>{{ ucfirst($cita->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Notas de evolución --}}
    <div class="seccion">
        <p class="seccion-titulo">Notas de evolución</p>
        @if($paciente->notasEvolucion->isEmpty())
            <p class="campo-valor vacio">Sin notas de evolución registradas.</p>
        @else
            @foreach($paciente->notasEvolucion as $nota)
                <div class="nota-evolucion">
                    <p class="nota-titulo">
                        {{ $nota->cita ? \Carbon\Carbon::parse($nota->cita->fecha)->format('d/m/Y') : 'Cita eliminada' }}
                        @if($nota->cita) · {{ $nota->cita->servicio->nombre ?? '—' }} @endif
                    </p>
                    @foreach([
                        'procedimiento' => 'Procedimiento',
                        'observaciones' => 'Observaciones',
                        'indicaciones' => 'Indicaciones',
                        'proxima_cita_sugerida' => 'Próxima cita sugerida',
                    ] as $campo => $etiqueta)
                        @if($nota->$campo)
                            <div class="bloque">
                                <p class="campo-etiqueta">{{ $etiqueta }}</p>
                                <p class="campo-valor bloque-texto">{{ $nota->$campo }}</p>
                            </div>
                        @endif
                    @endforeach
                    <p class="nota-autor">
                        Escrita por {{ $nota->creadoPor ? trim($nota->creadoPor->name . ' ' . $nota->creadoPor->apellidos) : 'un usuario eliminado' }}
                        el {{ $nota->created_at->format('d/m/Y h:i A') }}
                    </p>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Firma --}}
    <div class="firma">
        <div class="firma-linea"></div>
        <p class="firma-nombre">{{ $consultorio['nombre'] }}</p>
        <p class="firma-rol">{{ $consultorio['subtitulo'] }}@if($consultorio['cedula']) · Céd. Prof. {{ $consultorio['cedula'] }}@endif</p>
    </div>

    <p class="pie">
        Expediente clínico de {{ $paciente->name }} {{ $paciente->apellidos }} · Documento confidencial ·
        Impreso el {{ now()->format('d/m/Y') }} por {{ trim(auth()->user()->name . ' ' . auth()->user()->apellidos) }}
    </p>

</body>
</html>
