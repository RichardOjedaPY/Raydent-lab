<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Fotos - Pedido {{ $pedido->codigo_pedido ?? $pedido->id }}</title>

    <style>
        @page { margin: 6mm; }
        * { box-sizing: border-box; font-family: DejaVu Sans, Arial, sans-serif; }
        body { margin: 0; font-size: 10px; color: #111827; }

        /* Estabilidad DomPDF */
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        .no-break { page-break-inside: avoid; }

        /* Header */
        .hdr {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            margin-bottom: 6px;
        }
        .hdr-title { font-size: 13px; font-weight: 700; margin: 0; }
        .hdr-sub   { font-size: 10px; margin: 2px 0 0 0; color: #374151; }
        .hdr-meta  { font-size: 9px; margin: 2px 0 0 0; color: #6b7280; }
        .hdr-right { text-align: right; font-size: 9px; color: #6b7280; }
        .hdr-right strong { color: #111827; }

        /* Boxes */
        .box {
            border: 1px solid #cbd5e1;
            padding: 3px;
        }
        .label {
            margin-top: 3px;
            text-align: center;
            font-size: 9px;
            color: #374151;
        }

        /* Wrappers de imagen: recorte por overflow (DomPDF-friendly) */
        .imgwrap-top    { height: 130px; overflow: hidden; border: 1px solid #e5e7eb; }
        .imgwrap-occlus { height: 130px; overflow: hidden; border: 1px solid #e5e7eb; }
        .imgwrap-bot    { height: 110px; overflow: hidden; border: 1px solid #e5e7eb; }

        .imgwrap-top img,
        .imgwrap-occlus img,
        .imgwrap-bot img { width: 100%; display: block; }

        .placeholder {
            display: flex; align-items: center; justify-content: center;
            width: 100%;
            color: #94a3b8;
            border: 1px dashed #cbd5e1;
            font-size: 10px;
        }
        .ph-top    { height: 130px; }
        .ph-occlus { height: 130px; }
        .ph-bot    { height: 110px; }

        /* Espacios mínimos */
        .p2 { padding: 2px; }
        .mt4 { margin-top: 4px; }

        .footer {
            margin-top: 5px;
            font-size: 8.5px;
            color: #6b7280;
        }
    </style>
</head>
<body>
@php
    use Illuminate\Support\Facades\Storage;
    use Carbon\Carbon;

    // helper: obtener foto por slot
    $fotoBySlot = fn(string $slot) => $pedido->fotosRealizadas->firstWhere('slot', $slot);

    // helper: base64 para DomPDF (correcto con disco privado)
    $imgSrc = function ($foto) {
        if (!$foto) return null;
        try {
            $bin  = Storage::disk($foto->disk)->get($foto->path);
            $mime = $foto->mime ?: 'image/jpeg';
            return 'data:' . $mime . ';base64,' . base64_encode($bin);
        } catch (\Throwable $e) {
            return null;
        }
    };

    $slots = $slots ?? [];

    $pacienteNombre = trim(($pedido->paciente->apellido ?? '').' '.($pedido->paciente->nombre ?? ''));
    $doctor = $pedido->doctor_nombre ?: '—';
    $fecha  = $pedido->fecha_solicitud ?: optional($pedido->created_at)->format('Y-m-d') ?: now()->format('Y-m-d');

    // Edad (si existe fecha_nacimiento)
    $edadTxt = '—';
    $dob = optional($pedido->paciente)->fecha_nacimiento ?? null;
    if ($dob) {
        $ref = $pedido->fecha_solicitud ? Carbon::parse($pedido->fecha_solicitud) : now();
        $birth = Carbon::parse($dob);
        $years = $birth->diffInYears($ref);
        $months = $birth->copy()->addYears($years)->diffInMonths($ref);
        $edadTxt = "{$years}a, {$months}m";
    }

    // Orden EXACTO del ejemplo
    $top3 = ['fotografia_frontal','fotografia_perfil','fotografia_sonrisa'];
    $oclusales = ['oclusal_superior','oclusal_inferior'];
    $bottom3 = ['intraoral_derecho','intraoral_frontal','intraoral_izquierdo'];
@endphp

{{-- HEADER (sin flex, estable) --}}
<table class="hdr no-break">
    <tr>
        <td style="width:70%;">
            <div class="hdr-title">{{ $pacienteNombre ?: 'Paciente' }}</div>
            <div class="hdr-sub">Dr(a). {{ $doctor }}</div>
            <div class="hdr-meta">Edad: {{ $edadTxt }} · Fecha: {{ $fecha }} · Sexo: {{ $pedido->paciente->sexo ?? '—' }}</div>
        </td>
        <td class="hdr-right" style="width:30%;">
            Clínica: <strong>{{ $pedido->clinica->nombre ?? '—' }}</strong><br>
            Pedido: <strong>{{ $pedido->codigo_pedido ?? $pedido->codigo ?? ('#'.$pedido->id) }}</strong>
        </td>
    </tr>
</table>

{{-- LAYOUT PRINCIPAL --}}
<table class="no-break">
    <tr>
        {{-- Izquierda: 3 fotos faciales --}}
        <td style="width:72%;" class="p2">
            <table>
                <tr>
                    @foreach($top3 as $slot)
                        @php
                            $f = $fotoBySlot($slot);
                            $src = $imgSrc($f);
                            $label = $slots[$slot] ?? $slot;
                        @endphp
                        <td class="p2">
                            <div class="box">
                                @if($src)
                                    <div class="imgwrap-top"><img src="{{ $src }}" alt="{{ $label }}"></div>
                                @else
                                    <div class="placeholder ph-top">Sin imagen</div>
                                @endif
                                <div class="label">{{ $label }}</div>
                            </div>
                        </td>
                    @endforeach
                </tr>
            </table>
        </td>

        {{-- Derecha: 2 oclusales apiladas --}}
        <td style="width:28%;" class="p2">
            <table>
                @foreach($oclusales as $slot)
                    @php
                        $f = $fotoBySlot($slot);
                        $src = $imgSrc($f);
                        $label = $slots[$slot] ?? $slot;
                    @endphp
                    <tr>
                        <td class="p2">
                            <div class="box">
                                @if($src)
                                    <div class="imgwrap-occlus"><img src="{{ $src }}" alt="{{ $label }}"></div>
                                @else
                                    <div class="placeholder ph-occlus">Sin imagen</div>
                                @endif
                                <div class="label">{{ $label }}</div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>

{{-- Fila inferior: 3 intraorales --}}
<table class="no-break mt4">
    <tr>
        @foreach($bottom3 as $slot)
            @php
                $f = $fotoBySlot($slot);
                $src = $imgSrc($f);
                $label = $slots[$slot] ?? $slot;
            @endphp
            <td class="p2">
                <div class="box">
                    @if($src)
                        <div class="imgwrap-bot"><img src="{{ $src }}" alt="{{ $label }}"></div>
                    @else
                        <div class="placeholder ph-bot">Sin imagen</div>
                    @endif
                    <div class="label">{{ $label }}</div>
                </div>
            </td>
        @endforeach
    </tr>
</table>

<div class="footer">
    Documento generado por Raydent Lab · Solo uso clínico · Confidencial.
</div>

</body>
</html>
