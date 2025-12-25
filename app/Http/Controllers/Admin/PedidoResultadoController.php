<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\PedidoArchivo;
use App\Models\PedidoFotoRealizada;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\Audit;

class PedidoResultadoController extends Controller
{
    private function assertPuedeVerPedido(Pedido $pedido): void
    {
        $u = Auth::user();
        abort_unless($u, 403);

        // Admin o técnico: ven todo
        if ($u->hasRole('admin') || $u->hasRole('tecnico')) {
            return;
        }

        // Clínica: solo lo suyo
        if ($u->hasRole('clinica')) {
            $ok = $u->clinica_id && (int) $u->clinica_id === (int) $pedido->clinica_id;

            if (! $ok) {
                // 🧾 AUDIT: acceso denegado (clínica intentando ver pedido ajeno)
                Audit::log('resultados', 'denied', 'Acceso denegado a resultados', $pedido, [
                    'actor_role'        => 'clinica',
                    'actor_clinica_id'  => (int) ($u->clinica_id ?? 0),
                    'pedido_clinica_id' => (int) ($pedido->clinica_id ?? 0),
                ]);
            }

            abort_unless($ok, 403);
            return;
        }

        // 🧾 AUDIT: acceso denegado (rol no permitido)
        Audit::log('resultados', 'denied', 'Acceso denegado a resultados', $pedido, [
            'actor_role' => $u?->roles?->pluck('name')?->values()?->all() ?? null,
            'reason'     => 'role_not_allowed',
        ]);

        abort(403);
    }

    public function downloadArchivo(PedidoArchivo $archivo)
    {
        // ✅ Evita depender 100% de la relación
        $archivo->loadMissing('pedido');
        $pedido = $archivo->pedido ?: Pedido::findOrFail($archivo->pedido_id);

        $this->assertPuedeVerPedido($pedido);

        // ✅ Si el archivo no existe en disco, devolvemos 404 (no error 500)
        $exists = Storage::disk($archivo->disk)->exists($archivo->path);
        if (! $exists) {
            // 🧾 AUDIT: intento de descarga pero no existe en disco
            Audit::log('resultados', 'missing_file', 'Archivo no encontrado en disco', $pedido, [
                'archivo_id' => (int) $archivo->id,
                'disk'       => (string) $archivo->disk,
                'path'       => (string) $archivo->path,
            ]);
            abort(404, 'Archivo no encontrado.');
        }

        $name = $archivo->original_name ?: basename((string) $archivo->path);

        Audit::log('resultados', 'download', 'Archivo descargado', $pedido, [
            'archivo_id' => $archivo->id,
            'nombre'     => $name,
            'ext'        => $archivo->ext,
            'size'       => $archivo->size,
        ]);

        return Storage::disk($archivo->disk)->download($archivo->path, $name);
    }

    public function verFoto(PedidoFotoRealizada $foto)
    {
        $foto->loadMissing('pedido');
        $pedido = $foto->pedido ?: Pedido::findOrFail($foto->pedido_id);

        $this->assertPuedeVerPedido($pedido);

        $disk = $foto->disk ?: 'private';
        $path = (string) ($foto->path ?? '');

        if ($path === '' || !Storage::disk($disk)->exists($path)) {
            // 🧾 AUDIT: intento de ver foto pero no existe
            Audit::log('resultados', 'missing_photo', 'Foto no encontrada en disco', $pedido, [
                'foto_id' => (int) $foto->id,
                'disk'    => (string) $disk,
                'path'    => (string) $path,
                'slot'    => $foto->slot ?? null,
            ]);
            abort(404, 'Foto no encontrada.');
        }

        $ext = strtolower((string) ($foto->ext ?: pathinfo($path, PATHINFO_EXTENSION)));

        $mimeMap = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
            'bmp'  => 'image/bmp',
            'svg'  => 'image/svg+xml',
            'heic' => 'image/heic',
            'heif' => 'image/heif',
        ];

        $mime     = $foto->mime ?: ($mimeMap[$ext] ?? 'application/octet-stream');
        $filename = $foto->original_name ?: ('foto-' . $foto->id . ($ext ? ".{$ext}" : ''));

        $headers = [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'private, max-age=86400',
        ];

        // ✅ Log SIEMPRE, antes de devolver respuesta
        $modo = in_array($ext, ['heic', 'heif'], true) ? 'download' : 'inline';

        Audit::log('resultados', 'view_photo', 'Foto visualizada/descargada', $pedido, [
            'foto_id' => $foto->id,
            'slot'    => $foto->slot ?? null,
            'ext'     => $ext,
            'modo'    => $modo,
        ]);

        if ($modo === 'download') {
            return Storage::disk($disk)->download($path, $filename);
        }

        $fullPath = Storage::disk($disk)->path($path);

        if (is_file($fullPath)) {
            return response()->file($fullPath, $headers);
        }

        return Storage::disk($disk)->response($path, $filename, $headers, 'inline');
    }

    public function pdfFotos(Pedido $pedido)
    {
        $this->assertPuedeVerPedido($pedido);

        $pedido->load(['clinica', 'paciente', 'fotosRealizadas']);

        $pedido->setRelation(
            'fotosRealizadas',
            $pedido->fotosRealizadas->sortBy(fn($f) => (string) $f->slot)->values()
        );

        $slots = PedidoFotoRealizada::SLOTS;

        Audit::log('resultados', 'fotos_pdf', 'Fotos exportadas a PDF', $pedido, [
            'codigo_pedido'  => $pedido->codigo_pedido ?? $pedido->id,
            'cantidad_fotos' => (int) $pedido->fotosRealizadas->count(),
        ]);

        $pdf = Pdf::loadView('admin.pedidos.fotos_pdf', compact('pedido', 'slots'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('fotos-pedido-' . ($pedido->codigo_pedido ?? $pedido->id) . '.pdf');
    }
}
