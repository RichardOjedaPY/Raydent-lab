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

        /* Header Profesional */
        .hdr {
            border: 2px solid #0369a1;
            border-radius: 4px;
            padding: 10px 12px;
            margin-bottom: 10px;
            background: linear-gradient(to bottom, #f8fafc 0%, #ffffff 100%);
        }
        .hdr-top {
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .hdr-logo {
            height: 40px;
            max-width: 180px;
            object-fit: contain;
        }
        .hdr-empresa {
            font-size: 11px;
            color: #0369a1;
            font-weight: 600;
            margin: 2px 0 0 0;
        }
        .hdr-title { 
            font-size: 14px; 
            font-weight: 700; 
            margin: 0;
            color: #0f172a;
        }
        .hdr-sub { 
            font-size: 10px; 
            margin: 3px 0 0 0; 
            color: #374151; 
        }
        .hdr-meta { 
            font-size: 9px; 
            margin: 4px 0 0 0; 
            color: #64748b;
            background: #f1f5f9;
            padding: 4px 6px;
            border-radius: 3px;
            display: inline-block;
        }
        .hdr-right { 
            text-align: right; 
            font-size: 9px; 
            color: #64748b; 
        }
        .hdr-right strong { 
            color: #0f172a;
            font-size: 10px;
        }
        .hdr-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 0.5px;
        }

        /* Boxes - MÁS GRANDES */
        .box {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }
        .label {
            margin-top: 4px;
            text-align: center;
            font-size: 9px;
            color: #374151;
            font-weight: 500;
        }

        /* Wrappers de imagen - INCREMENTADOS para mejor visualización */
        .imgwrap-top    { height: 160px; overflow: hidden; border: 1px solid #e5e7eb; }
        .imgwrap-occlus-v { height: 180px; overflow: hidden; border: 1px solid #e5e7eb; } /* VERTICAL */
        .imgwrap-bot    { height: 140px; overflow: hidden; border: 1px solid #e5e7eb; }

        .imgwrap-top img,
        .imgwrap-bot img { 
            width: 100%; 
            display: block; 
        }
        
        /* Oclusales en orientación natural (horizontal) */
        .imgwrap-occlus-v img { 
            width: 100%; 
            display: block;
        }

        .placeholder {
            display: flex; align-items: center; justify-content: center;
            width: 100%;
            color: #94a3b8;
            border: 1px dashed #cbd5e1;
            font-size: 10px;
        }
        .ph-top    { height: 160px; }
        .ph-occlus-v { height: 180px; }
        .ph-bot    { height: 140px; }

        /* Espacios */
        .p2 { padding: 2px; }
        .mt6 { margin-top: 6px; }

        .footer {
            margin-top: 6px;
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

    // helper: base64 para DomPDF
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

    // Logo
    $logoPath = public_path('img/raydent-logo.png');
    $logoSrc = null;
    if (file_exists($logoPath)) {
        try {
            $logoData = file_get_contents($logoPath);
            $logoMime = 'image/png';
            $logoSrc = 'data:' . $logoMime . ';base64,' . base64_encode($logoData);
        } catch (\Throwable $e) {
            $logoSrc = null;
        }
    }

    $pacienteNombre = trim(($pedido->paciente->apellido ?? '').' '.($pedido->paciente->nombre ?? ''));
    $doctor = $pedido->doctor_nombre ?: '—';

    // Fecha
    $fechaRaw = $pedido->fecha_solicitud
        ?: optional($pedido->created_at)->toDateString()
        ?: now()->toDateString();

    try {
        $fechaTxt = Carbon::parse($fechaRaw)->format('Y-m-d');
    } catch (\Throwable $e) {
        $fechaTxt = (string) $fechaRaw;
    }

    // Edad
    $edadTxt = '—';
    $pac = $pedido->paciente ?? null;

    if ($pac) {
        if (!is_null($pac->edad) && $pac->edad !== '') {
            $edadTxt = (int) $pac->edad . ' años';
        } elseif (!empty($pac->fecha_nacimiento)) {
            try {
                $ref   = Carbon::parse($fechaRaw);
                $birth = Carbon::parse($pac->fecha_nacimiento);

                $years  = $birth->diffInYears($ref);
                $months = $birth->copy()->addYears($years)->diffInMonths($ref);

                $edadTxt = $months > 0 ? "{$years}a, {$months}m" : "{$years}a";
            } catch (\Throwable $e) {
                $edadTxt = '—';
            }
        }
    }

    // Sexo/Género
    $sexoTxt = '—';
    if ($pac) {
        $sexoTxt = match ($pac->genero ?? null) {
            'M' => 'Masculino',
            'F' => 'Femenino',
            'O' => 'Otro',
            default => '—',
        };
    }

    // Orden de fotos
    $top3 = ['fotografia_frontal','fotografia_perfil','fotografia_sonrisa'];
    $oclusales = ['oclusal_superior','oclusal_inferior'];
    $bottom3 = ['intraoral_derecho','intraoral_frontal','intraoral_izquierdo'];
@endphp

{{-- HEADER PROFESIONAL --}}
<table class="hdr no-break">
    {{-- Fila superior: Logo y datos del pedido --}}
    <tr class="hdr-top">
        <td style="width:50%; vertical-align: middle;">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" alt="Raydent Lab" class="hdr-logo">
            @else
                <div style="font-size: 16px; font-weight: 700; color: #0369a1;">Raydent</div>
            @endif
            <div class="hdr-empresa">Radiología Odontológica Digital</div>
        </td>
        <td class="hdr-right" style="width:50%; vertical-align: middle;">
            <div class="hdr-label">Pedido</div>
            <strong>{{ $pedido->codigo_pedido ?? $pedido->codigo ?? ('#'.$pedido->id) }}</strong>
            <div style="margin-top: 4px;">
                <span class="hdr-label">Fecha:</span> <strong>{{ $fechaTxt }}</strong>
            </div>
        </td>
    </tr>
    
    {{-- Fila inferior: Datos del paciente --}}
    <tr>
        <td style="width:65%; vertical-align: top; padding-top: 8px;">
            <div class="hdr-title">{{ $pacienteNombre ?: 'Paciente' }}</div>
            <div class="hdr-sub">Dr(a). {{ $doctor }}</div>
            <div class="hdr-meta">
                <strong>Edad:</strong> {{ $edadTxt }} · 
                <strong>Sexo:</strong> {{ $sexoTxt }}
            </div>
        </td>
        <td class="hdr-right" style="width:35%; vertical-align: top; padding-top: 8px;">
            <div style="margin-bottom: 3px;">
                <span class="hdr-label">Clínica</span><br>
                <strong>{{ $pedido->clinica->nombre ?? '—' }}</strong>
            </div>
        </td>
    </tr>
</table>

{{-- LAYOUT PRINCIPAL: 3 fotos faciales + 2 oclusales VERTICALES --}}
<table class="no-break">
    <tr>
        {{-- Izquierda: 3 fotos faciales (70% del ancho) --}}
        <td style="width:68%;" class="p2">
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

        {{-- Derecha: 2 oclusales VERTICALES apiladas (32% del ancho) --}}
        <td style="width:32%;" class="p2">
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
                                    <div class="imgwrap-occlus-v"><img src="{{ $src }}" alt="{{ $label }}"></div>
                                @else
                                    <div class="placeholder ph-occlus-v">Sin imagen</div>
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

{{-- Fila inferior: 3 intraorales MÁS GRANDES --}}
<table class="no-break mt6">
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