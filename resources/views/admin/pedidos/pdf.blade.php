 {{-- resources/views/admin/pedidos/pdf.blade.php --}}
@php
use Carbon\Carbon;

// Helper para checkbox visual
$cb = function ($flag) {
    return $flag ? '☒' : '☐';
};

$paciente = $pedido->paciente;
$clinica  = $pedido->clinica;

// Fechas
$fechaSolicitud = $pedido->fecha_solicitud
    ? Carbon::parse($pedido->fecha_solicitud)->format('d/m/Y')
    : '';

$fechaAgendada = $pedido->fecha_agendada
    ? Carbon::parse($pedido->fecha_agendada)->format('d/m/Y')
    : '';

if ($pedido->hora_agendada instanceof Carbon) {
    $horaAgendada = $pedido->hora_agendada->format('H:i');
} else {
    $horaAgendada = $pedido->hora_agendada
        ? Carbon::parse($pedido->hora_agendada)->format('H:i')
        : '';
}

// Sets de piezas como strings
$periapicalSet = collect($periapical ?? [])->map(fn ($v) => (string) $v)->all();
$tomografiaSet = collect($tomografia ?? [])->map(fn ($v) => (string) $v)->all();

// Catálogos
$fotosTipos = [
    'frente'            => 'Frente',
    'perfil_derecho'    => 'Perfil derecho',
    'perfil_izquierdo'  => 'Perfil izquierdo',
    'sonriendo'         => 'Sonriendo (1/3 inferior de la face)',
    'frontal_oclusion'  => 'Frontal en oclusión',
    'lateral_derecha'   => 'Lateral derecha',
    'lateral_izquierda' => 'Lateral izquierda',
    'oclusal_superior'  => 'Oclusal superior',
    'oclusal_inferior'  => 'Oclusal inferior',
];

$cefalometriasTipos = [
    'usp'              => 'Usp',
    'unicamp'          => 'Unicamp',
    'usp_unicamp'      => 'Usp/unicamp',
    'tweed'            => 'Tweed',
    'steiner'          => 'Steiner',
    'homem_neto'       => 'Homem Neto',
    'downs'            => 'Downs',
    'mcnamara'         => 'McNamara',
    'bimler'           => 'Bimler',
    'jarabak'          => 'Jarabak',
    'profis'           => 'Profis',
    'ricketts'         => 'Ricketts',
    'ricketts_frontal' => 'Ricketts frontal',
    'petrovic'         => 'Petrovic',
    'sassouni'         => 'Sassouni',
    'schwarz'          => 'Schwarz',
    'trevisi'          => 'Trevisi',
    'valieri'          => 'Valieri',
    'rocabado'         => 'Rocabado',
    'adenoides'        => 'Adenoides',
];

$documentaciones = [
    'doc_simplificada_1'        => 'DOC. Simplificada 1 (DIGITAL)',
    'doc_simplificada_2'        => 'DOC. Simplificada 2 (DIGITAL)',
    'doc_completa_digital'      => 'Documentación completa (DIGITAL)',
    'doc_completa_fotos_modelo' => 'Documentación completa (fotos y modelo)',
];

$docDetalles = [
    'doc_simplificada_1'        => 'Panorámica, teleradiografía lateral, (8) fotos',
    'doc_simplificada_2'        => 'Panorámica, teleradiografía lateral (6) fotos',
    'doc_completa_digital'      => 'Panorámica, telerradiografía lateral (c/trazado), (8) fotos, escaneado intraoral',
    'doc_completa_fotos_modelo' => 'Panorámica, telerradiografía lateral (c/trazado), (8) fotos, escaneado intraoral, modelo',
];

$fotosSel = collect($fotosSeleccionadas ?? []);
$cefasSel = collect($cefalometriasSeleccionadas ?? []);
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Pedido {{ $pedido->codigo_pedido }}</title>
<style>
    @page { margin: 8mm; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        color: #333;
        line-height: 1.1;
    }

    /* Utilidades */
    .w-100 { width: 100%; }
    .w-50 { width: 50%; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .font-bold { font-weight: bold; }
    .uppercase { text-transform: uppercase; }

    .page { }
    .page-break { page-break-after: always; }

    /* Colores Raydent */
    .bg-blue { background-color: #005596; color: white; }
    .text-blue { color: #005596; }

    /* Tablas */
    .table-clean { width: 100%; border-collapse: collapse; }
    .table-clean td { vertical-align: top; padding: 2px; }

    .header-section {
        padding: 3px 5px;
        margin-top: 5px;
        margin-bottom: 5px;
        font-weight: bold;
        font-size: 11px;
        text-align: center;
        border-radius: 4px;
    }

    .sub-title {
        font-weight: bold;
        text-transform: uppercase;
        font-size: 10px;
        margin-bottom: 3px;
        color: #005596;
    }

    .box-container {
        border: 1px solid #ccc;
        border-radius: 5px;
        padding: 5px;
        margin-bottom: 5px;
    }

    .input-line {
        border-bottom: 1px solid #999;
        padding: 0 5px;
        display: inline-block;
    }

    .check-row { margin-bottom: 2px; }

    /* Odontograma */
    .tooth-table {
        margin: 0 auto;
        border-collapse: collapse;
    }
    .tooth-table td {
        width: 14px;
        height: 14px;
        border: 1px solid #666;
        text-align: center;
        font-size: 8px;
    }
    .tooth-table td.no-border {
        border: none;
        font-weight: bold;
        width: 10px;
    }
    .tooth-table td.selected {
        background-color: #005596;
        color: #fff;
    }

    /* Footer */
    .footer-legal {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: #005596;
        color: white;
        text-align: center;
        font-size: 8px;
        padding: 5px;
        border-radius: 4px 4px 0 0;
    }
</style>
</head>
<body>

{{-- ==================== PÁGINA 1 ==================== --}}
<div class="page page-break">

    {{-- Encabezado --}}
    <table class="table-clean" style="border-bottom: 2px solid #005596; margin-bottom: 5px;">
        <tr>
            <td width="60%">
                <h1 style="margin: 0; color: #005596; font-size: 28px;">Raydent</h1>
                <small style="color: #666;">Radiología Odontológica Digital</small>
            </td>
            <td width="40%" class="text-right">
                <div class="text-blue font-bold" style="font-size: 12px;">
                    Pedido: {{ $pedido->codigo_pedido }}
                </div>
                <div>Fecha: {{ $fechaSolicitud }}</div>
                 
            </td>
        </tr>
    </table>

    {{-- Datos Paciente / Doctor --}}
    <div class="box-container">
        <table class="table-clean">
            <tr>
                <td width="12%" class="font-bold">Paciente:</td>
                <td width="58%" style="border-bottom: 1px solid #ccc;">
                    {{ $paciente ? $paciente->apellido.' '.$paciente->nombre : '' }}
                </td>
                <td width="5%" class="font-bold">CI:</td>
                <td width="25%" style="border-bottom: 1px solid #ccc;">
                    {{ $pedido->paciente_documento }}
                </td>
            </tr>
            <tr>
                <td class="font-bold">Dr(a):</td>
                <td style="border-bottom: 1px solid #ccc;">{{ $pedido->doctor_nombre }}</td>
                <td class="font-bold">Tel:</td>
                <td style="border-bottom: 1px solid #ccc;">{{ $pedido->doctor_telefono }}</td>
            </tr>
            <tr>
                <td class="font-bold">Dirección:</td>
                <td colspan="3" style="border-bottom: 1px solid #ccc;">{{ $pedido->direccion }}</td>
            </tr>
            <tr>
                <td class="font-bold">E-mail:</td>
                <td style="border-bottom: 1px solid #ccc;">{{ $pedido->doctor_email }}</td>
                <td colspan="2"></td>
            </tr>
        </table>

        <div style="background-color: #e6f2ff; padding: 3px; margin-top: 4px; border-radius: 3px;">
            <span class="text-blue font-bold">AGENDADO PARA:</span> {{ $fechaAgendada }}
            <span style="float: right;">
                <span class="text-blue font-bold">HORA:</span> {{ $horaAgendada }}
            </span>
        </div>
    </div>

    {{-- EXÁMENES RADIOGRÁFICOS --}}
    <div class="header-section bg-blue">EXÁMENES RADIOGRÁFICOS</div>

    <table class="table-clean">
        <tr>
            {{-- PANORÁMICA --}}
            <td width="33%">
                <div class="box-container" style="height: 110px;">
                    <div class="sub-title">PANORÁMICA</div>
                    <div class="check-row">{{ $cb($pedido->rx_panoramica_convencional) }} Convencional</div>
                    <div class="check-row">{{ $cb($pedido->rx_panoramica_trazado_implante) }} Con trazado p/ implante</div>
                    <div style="margin-left: 15px; font-size: 9px;">
                        de Región:
                        <span class="input-line" style="min-width: 50px;">
                            {{ $pedido->rx_panoramica_trazado_region }}
                        </span>
                    </div>
                    <div class="check-row" style="margin-top: 3px;">
                        {{ $cb($pedido->rx_panoramica_atm_boca_abierta_cerrada) }}
                        ATM (abierta/cerrada)
                    </div>
                </div>
            </td>

            {{-- TELERRADIOGRAFÍA --}}
            <td width="34%">
                <div class="box-container" style="height: 110px;">
                    <div class="sub-title">TELERRADIOGRAFÍA</div>
                    <div class="check-row">{{ $cb($pedido->rx_teleradiografia_lateral) }} Lateral</div>
                    <div class="check-row">{{ $cb($pedido->rx_teleradiografia_frontal_pa) }} Frontal (PA)</div>
                    <div class="check-row">{{ $cb($pedido->rx_teleradiografia_waters) }} Waters (senos)</div>
                    <div class="check-row">
                        {{ $cb($pedido->rx_teleradiografia_indice_carpal_edad_osea) }}
                        Índice carpal / edad
                    </div>
                </div>
            </td>

            {{-- INTERPROXIMAL --}}
            <td width="33%">
                <div class="box-container" style="height: 110px;">
                    <div class="sub-title">INTERPROXIMAL</div>
                    <div class="check-row">
                        {{ $cb($pedido->rx_interproximal_premolares_derecho) }} Pre-Molares Der.
                    </div>
                    <div class="check-row">
                        {{ $cb($pedido->rx_interproximal_premolares_izquierdo) }} Pre-Molares Izq.
                    </div>
                    <div class="check-row">
                        {{ $cb($pedido->rx_interproximal_molares_derecho) }} Molares Der.
                    </div>
                    <div class="check-row">
                        {{ $cb($pedido->rx_interproximal_molares_izquierdo) }} Molares Izq.
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- PERIAPICAL --}}
    <div class="text-center sub-title" style="margin-top: 5px;">PERIAPICAL</div>
    <div class="box-container">
        <table class="table-clean">
            <tr>
                <td width="40%">
                    <div class="check-row">
                        {{ $cb($pedido->rx_periapical_dientes_senalados) }} Dientes Señalados
                    </div>
                    <div class="check-row">
                        {{ $cb($pedido->rx_periapical_status_radiografico) }}
                        Status Radiográfico (todos)
                    </div>
                    <div class="check-row">
                        {{ $cb($pedido->rx_periapical_tecnica_clark) }} Técnica de Clark
                    </div>
                    <div style="margin-top: 5px;">
                        Región:
                        <span class="input-line" style="min-width: 80px;">
                            {{ $pedido->rx_periapical_region }}
                        </span>
                    </div>
                </td>
                <td width="60%">
                    <table class="tooth-table">
                        {{-- Superiores --}}
                        <tr>
                            <td class="no-border">D</td>
                            @foreach ([18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28] as $p)
                                <td class="{{ in_array((string)$p, $periapicalSet) ? 'selected' : '' }}">
                                    {{ $p }}
                                </td>
                            @endforeach
                            <td class="no-border">I</td>
                        </tr>
                        {{-- Inferiores --}}
                        <tr>
                            <td class="no-border"></td>
                            @foreach ([48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38] as $p)
                                <td class="{{ in_array((string)$p, $periapicalSet) ? 'selected' : '' }}">
                                    {{ $p }}
                                </td>
                            @endforeach
                            <td class="no-border"></td>
                        </tr>
                        {{-- Temporales Sup --}}
                        <tr>
                            <td class="no-border"></td>
                            <td colspan="3" class="no-border"></td>
                            @foreach ([55,54,53,52,51,61,62,63,64,65] as $p)
                                <td class="{{ in_array((string)$p, $periapicalSet) ? 'selected' : '' }}">
                                    {{ $p }}
                                </td>
                            @endforeach
                            <td colspan="3" class="no-border"></td>
                            <td class="no-border"></td>
                        </tr>
                        {{-- Temporales Inf --}}
                        <tr>
                            <td class="no-border"></td>
                            <td colspan="3" class="no-border"></td>
                            @foreach ([85,84,83,82,81,71,72,73,74,75] as $p)
                                <td class="{{ in_array((string)$p, $periapicalSet) ? 'selected' : '' }}">
                                    {{ $p }}
                                </td>
                            @endforeach
                            <td colspan="3" class="no-border"></td>
                            <td class="no-border"></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- CON / SIN INFORME --}}
    <div class="text-center font-bold" style="margin: 5px 0;">
        {{ $cb((bool)$pedido->rx_con_informe) }} CON INFORME
        &nbsp;&nbsp;&nbsp;&nbsp;
        {{ $cb(! (bool)$pedido->rx_con_informe) }} SIN INFORME
    </div>

    {{-- DOCUMENTACIÓN --}}
    <div class="header-section bg-blue">DOCUMENTACIÓN</div>
    <div class="box-container">
        @foreach ($documentaciones as $key => $label)
            <div style="margin-bottom: 4px;">
                <span style="font-size: 12px; color: #005596;">
                    {{ $cb($pedido->documentacion_tipo === $key) }}
                </span>
                <span class="font-bold">{{ $label }}</span>
                @if (isset($docDetalles[$key]))
                    <div style="margin-left: 18px; font-size: 9px; color: #555;">
                        {{ $docDetalles[$key] }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Firma --}}
    <div style="margin-top: 20px; text-align: right;">
        <div style="display: inline-block; width: 250px; text-align: center;">
            <div style="height: 40px; border-bottom: 1px solid #333;"></div>
            <div style="font-size: 9px; margin-top: 2px;">Firma y Sello del Odontólogo</div>
        </div>
    </div>

    <div class="footer-legal">
        Av. Cesar Gionotti c/ Calle Cnel Bogado - Hernandarias · Edificio Dinámica al costado de IPS<br>
        Cel. (0973) 665 779 · www.raydentradiologia.com.py · raydentradiologia511@gmail.com
    </div>
</div>

{{-- ==================== PÁGINA 2 ==================== --}}
<div class="page" style="position: relative;">

    <div class="header-section bg-blue" style="margin-top: 0;">INFORMACIÓN ADICIONAL</div>

    {{-- FOTOS --}}
    <div class="text-center sub-title">FOTOS</div>
    <div class="box-container">
        <table class="table-clean">
            <tr>
                @foreach (array_chunk($fotosTipos, 5, true) as $chunk)
                    <td width="50%">
                        @foreach ($chunk as $k => $v)
                            <div class="check-row">
                                {{ $cb($fotosSel->contains($k)) }} {{ $v }}
                            </div>
                        @endforeach
                    </td>
                @endforeach
            </tr>
        </table>
    </div>

    {{-- CEFALOMETRÍAS --}}
    <div class="text-center sub-title text-blue">Estudios Cefalométricos</div>
    <div class="box-container">
        <table class="table-clean">
            <tr>
                @foreach (array_chunk($cefalometriasTipos, 5, true) as $chunk)
                    <td width="25%">
                        @foreach ($chunk as $k => $v)
                            <div class="check-row">
                                {{ $cb($cefasSel->contains($k)) }} {{ $v }}
                            </div>
                        @endforeach
                    </td>
                @endforeach
            </tr>
        </table>
    </div>

    <div class="header-section bg-blue">EXÁMENES COMPLEMENTARIOS</div>

    <div class="box-container">
        <div class="sub-title text-center" style="margin-top:0;">Escaneamiento Intraoral</div>
        <table class="table-clean">
            <tr>
                <td width="50%">
                    <div class="check-row">
                        {{ $cb($pedido->intraoral_maxilar_superior) }} Maxilar Superior
                    </div>
                    <div class="check-row">
                        {{ $cb($pedido->intraoral_mandibula) }} Mandíbula
                    </div>
                    <div class="check-row">
                        {{ $cb($pedido->intraoral_maxilar_mandibula_completa) }}
                        Maxilar y Mandíbula Completa
                    </div>
                </td>
                <td width="50%">
                    <div class="font-bold">Modelo</div>
                    <div class="check-row">
                        {{ $cb($pedido->intraoral_modelo_con_base) }} Con base (Estudio)
                    </div>
                    <div class="check-row">
                        {{ $cb($pedido->intraoral_modelo_sin_base) }} Sin base (Trabajo)
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- TOMOGRAFÍA --}}
    <div class="header-section bg-blue">
        TOMOGRAFÍA COMPUTADORIZADA VOLUMÉTRICA DE ALTA RESOLUCIÓN
    </div>

    <table class="table-clean box-container" style="border:none; padding: 0;">
        <tr>
            <td width="60%" style="padding-right: 10px;">
                 <div class="check-row font-bold">
                     {{ $cb($pedido->ct_maxilar_completa) }} TOMOGRAFÍA MAXILAR COMPLETA
                 </div>
                 <div class="check-row font-bold">
                     {{ $cb($pedido->ct_mandibula_completa) }} TOMOGRAFÍA MANDÍBULA COMPLETA
                 </div>
                 <div class="check-row">
                     {{ $cb($pedido->ct_maxilar_arco_cigomatico) }} MAXILAR COMPLETA ARCO CIGOMÁTICO
                 </div>
                 <div class="check-row">{{ $cb($pedido->ct_atm) }} TOMOGRAFÍA DE ATM</div>

                 <div style="margin-top: 5px;">
                     {{ $cb($pedido->ct_parcial) }} TOMOGRAFÍA PARCIAL Zona:
                     <span class="input-line" style="width: 80px;">{{ $pedido->ct_parcial_zona }}</span>
                 </div>
                 <div style="margin-top: 5px;">
                     {{ $cb($pedido->ct_region_senalada_abajo) }} TOMOGRAFÍA REGIÓN SEÑALADAS ABAJO
                 </div>
            </td>

            <td width="40%" style="background-color: #f5f5f5; border-radius: 5px; padding: 5px;">
                <div class="sub-title">Formas de entrega:</div>
                <div class="check-row">{{ $cb($pedido->entrega_pdf) }} Digital (PDF)</div>
                <div class="check-row">{{ $cb($pedido->entrega_papel_fotografico) }} Papel fotográfico</div>
                <div class="check-row">{{ $cb($pedido->entrega_dicom) }} Dicom</div>
                <div class="check-row">
                    {{ $cb($pedido->entrega_software_visualizacion) }} Software para Visualización:
                </div>
                <div style="margin-left: 15px; border-bottom: 1px solid #ccc;">
                    {{ $pedido->entrega_software_detalle }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Odontograma de tomografía --}}
    <div style="margin-top: 10px;">
        <table class="table-clean">
            <tr>
                <td width="55%">
                    <div style="font-size: 8px; font-weight: bold; margin-bottom: 3px;">
                        REGIÓN SEÑALADA ABAJO:
                    </div>
                    <table class="tooth-table">
                        <tr>
                            <td class="no-border">D</td>
                            @foreach ([18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28] as $p)
                                <td class="{{ in_array((string)$p, $tomografiaSet) ? 'selected' : '' }}">
                                    {{ $p }}
                                </td>
                            @endforeach
                            <td class="no-border">I</td>
                        </tr>
                        <tr>
                            <td class="no-border"></td>
                            @foreach ([48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38] as $p)
                                <td class="{{ in_array((string)$p, $tomografiaSet) ? 'selected' : '' }}">
                                    {{ $p }}
                                </td>
                            @endforeach
                            <td class="no-border"></td>
                        </tr>
                    </table>
                </td>
                <td width="45%">
                    <div style="font-size: 9px; font-weight: bold;">DESCRIBIR EL CASO</div>
                    <div style="border: 1px solid #ccc; height: 50px; padding: 3px; font-size: 9px;">
                        {!! nl2br(e($pedido->descripcion_caso)) !!}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- FINALIDAD --}}
    <div class="header-section bg-blue">FINALIDAD DEL EXAMEN</div>
    <div class="box-container">
        <table class="table-clean" style="font-size: 9px;">
            <tr>
                <td width="50%">
                    <div class="check-row">{{ $cb($pedido->finalidad_implantes) }} IMPLANTES</div>
                    <div class="check-row">{{ $cb($pedido->finalidad_dientes_incluidos) }} DIENTES INCLUIDOS</div>
                    <div class="check-row">{{ $cb($pedido->finalidad_terceros_molares) }} 3° MOLARES</div>
                    <div class="check-row">{{ $cb($pedido->finalidad_supernumerarios) }} LOCALIZACIÓN DE SUPERNUMERARIOS</div>
                </td>
                <td width="50%">
                    <div class="check-row">{{ $cb($pedido->finalidad_perforacion_radicular) }} PERFORACIÓN RADICULAR</div>
                    <div class="check-row">{{ $cb($pedido->finalidad_sospecha_fractura) }} SOSPECHA DE FRACTURA</div>
                    <div class="check-row">{{ $cb($pedido->finalidad_patologia) }} PATOLOGÍA</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-legal">
        <div style="margin-bottom: 2px;">
            Av. Cesar Gionotti c/ Calle Cnel Bogado - Hernandarias · Edificio Dinámica al costado de IPS<br>
            Cel. (0973) 665 779 · www.raydentradiologia.com.py
        </div>
        <div style="border-top: 1px solid rgba(255,255,255,0.5); padding-top: 2px; font-size: 7px;">
            Los valores del examen informados por teléfono son aproximados y serán confirmados en nuestra recepción
        </div>
    </div>
</div>

</body>
</html>
